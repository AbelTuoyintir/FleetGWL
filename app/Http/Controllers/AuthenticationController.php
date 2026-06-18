<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\Support\Facades\Mail;
use App\Mail\GoogleStyleLoginAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthenticationController extends Controller
{
    // Google-style notification logic
    protected $trustedDeviceDays = 30; // Trust device for 30 days
    protected $suspiciousScoreThreshold = 70; // Out of 100
    
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(['token' => $token, 'email' => $request->email]);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find the user
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Account is disabled or does not exist.',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            $this->logSecurityEvent($user->email, 'failed_login', $request);
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        // Check if account is active
        if (isset($user->status) && $user->status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact administrator.',
            ]);
        }

        // GOOGLE-STYLE: Calculate security score
        $securityContext = $this->calculateSecurityContext($user, $request);
        
        // GOOGLE-STYLE: Determine if this is a "critical" login that needs notification
        $notificationLevel = $this->determineNotificationLevel($user, $request, $securityContext);
        
        // Log the user in
        Auth::login($user, $request->boolean('remember'));
        
        // GOOGLE-STYLE: Update device fingerprint and trusted devices
        $deviceFingerprint = $this->getDeviceFingerprint($request);
        
        if ($request->boolean('remember')) {
            $this->trustDevice($user, $deviceFingerprint, $request);
        }
        
        // Update login metadata
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => $deviceFingerprint,
            'login_count' => \DB::raw('login_count + 1'),
            'last_security_score' => $securityContext['score'],
        ]);
        
        // Regenerate session
        $request->session()->regenerate();
        
        // GOOGLE-STYLE: Send notification based on level
        if ($notificationLevel !== 'none') {
            $this->sendGoogleStyleAlert($user, $request, $securityContext, $notificationLevel);
        }
        
        // Log successful login
        $this->logSecurityEvent($user->email, 'successful_login', $request, ['security_score' => $securityContext['score']]);
        
        // GOOGLE-STYLE: Show in-app notification on dashboard
        session()->flash('security_notification', $this->getInAppNotification($securityContext, $notificationLevel));
        
        // Redirect based on role
        return $this->redirectBasedOnRole($user);
    }
    
    /**
     * GOOGLE-STYLE: Calculate security context and score for this login
     */
    protected function calculateSecurityContext($user, $request)
    {
        $score = 100; // Start with perfect score
        $riskFactors = [];
        $location = $this->getLocationFromIp($request->ip());
        $deviceFingerprint = $this->getDeviceFingerprint($request);
        
        // Factor 1: Is this a new device? (-20 points)
        $isNewDevice = !$this->isTrustedDevice($user, $deviceFingerprint);
        if ($isNewDevice) {
            $score -= 20;
            $riskFactors[] = 'new_device';
        }
        
        // Factor 2: Location changed significantly? (-15 to -30 points)
        if ($user->last_login_location && $user->last_login_location !== $location) {
            $distance = $this->calculateLocationDistance($user->last_login_location, $location);
            if ($distance > 100) { // More than 100km away
                $score -= 30;
                $riskFactors[] = 'location_changed_drastic';
            } elseif ($distance > 10) {
                $score -= 15;
                $riskFactors[] = 'location_changed';
            }
        }
        
        // Factor 3: Unusual time? (-10 points)
        $currentHour = (int)now()->format('H');
        $userTypicalHours = $this->getTypicalLoginHours($user);
        if (!in_array($currentHour, $userTypicalHours)) {
            $score -= 10;
            $riskFactors[] = 'unusual_time';
        }
        
        // Factor 4: Multiple failed attempts before success (-25 points)
        $recentFailedAttempts = \DB::table('security_events')
            ->where('user_identifier', $user->email)
            ->where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();
            
        if ($recentFailedAttempts >= 3) {
            $score -= 25;
            $riskFactors[] = 'multiple_failures';
        }
        
        // Factor 5: Different browser/user agent (-5 points)
        if ($user->last_login_user_agent && $user->last_login_user_agent !== $request->userAgent()) {
            $score -= 5;
            $riskFactors[] = 'browser_changed';
        }
        
        // Factor 6: High risk location? (-15 points)
        $highRiskCountries = ['NG', 'KE', 'ZA', 'EG', 'MA']; // Example
        if (in_array($this->getCountryCode($request->ip()), $highRiskCountries)) {
            $score -= 15;
            $riskFactors[] = 'high_risk_location';
        }
        
        // Factor 7: User's role affects threshold
        if (in_array($user->role, ['admin', 'super_admin'])) {
            $score -= 5; // Stricter for admins
        }
        
        // Ensure score is within 0-100
        $score = max(0, min(100, $score));
        
        // Determine level
        $level = 'low';
        if ($score < 40) {
            $level = 'critical';
        } elseif ($score < 70) {
            $level = 'medium';
        }
        
        return [
            'score' => $score,
            'level' => $level,
            'risk_factors' => $riskFactors,
            'is_new_device' => $isNewDevice,
            'location' => $location,
            'device_fingerprint' => $deviceFingerprint,
            'time' => now()->format('g:i A'),
            'date' => now()->format('F j, Y'),
            'day_of_week' => now()->format('l'),
        ];
    }
    
    /**
     * GOOGLE-STYLE: Determine if we should notify and with what urgency
     */
    protected function determineNotificationLevel($user, $request, $securityContext)
    {
        $score = $securityContext['score'];
        $level = $securityContext['level'];
        
        // CRITICAL: Always notify for low scores
        if ($level === 'critical') {
            return 'critical';
        }
        
        // MEDIUM: Notify for medium risk
        if ($level === 'medium') {
            // But check if we already notified recently for similar context
            $lastNotification = $user->last_security_notification_at;
            $lastRiskLevel = $user->last_risk_level;
            
            if ($lastNotification && $lastNotification->diffInHours(now()) < 1 && $lastRiskLevel === 'medium') {
                return 'none'; // Don't spam
            }
            return 'medium';
        }
        
        // LOW RISK: Only notify for new devices or location changes
        if ($securityContext['is_new_device']) {
            // Check if we already trust this device type
            $deviceType = $this->getDeviceType($request->userAgent());
            $trustedDeviceTypes = json_decode($user->trusted_device_types ?? '[]', true);
            
            if (!in_array($deviceType, $trustedDeviceTypes)) {
                return 'info';
            }
        }
        
        // GOOGLE-STYLE: For drivers, only notify for significant events
        if ($user->role === 'driver') {
            $lastAnyNotification = $user->last_security_notification_at;
            if ($lastAnyNotification && $lastAnyNotification->diffInHours(now()) < 12) {
                return 'none'; // Drivers get max 2 notifications per day
            }
            
            // Only notify drivers for new devices or location changes
            if ($securityContext['is_new_device'] || in_array('location_changed', $securityContext['risk_factors'])) {
                return 'info';
            }
            
            return 'none';
        }
        
        // For admins: Notify for any new device or location change
        if (in_array($user->role, ['admin', 'super_admin', 'manager'])) {
            if ($securityContext['is_new_device'] || count($securityContext['risk_factors']) > 0) {
                return 'medium';
            }
        }
        
        // Default: No notification for routine logins from trusted devices
        return 'none';
    }
    
    /**
     * GOOGLE-STYLE: Send beautiful, contextual email alert
     */
    protected function sendGoogleStyleAlert($user, $request, $securityContext, $level)
    {
        try {
            $alertData = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => ucfirst($user->role),
                'alert_level' => $level, // 'critical', 'medium', 'info'
                'security_score' => $securityContext['score'],
                'risk_factors' => $securityContext['risk_factors'],
                'location' => $securityContext['location'],
                'device_info' => $this->getDetailedDeviceInfo($request),
                'login_time' => $securityContext['time'],
                'login_date' => $securityContext['date'],
                'day_of_week' => $securityContext['day_of_week'],
                'ip_address' => $request->ip(),
                'is_new_device' => $securityContext['is_new_device'],
                'action_required' => $level === 'critical',
                'trusted_devices_count' => $this->countTrustedDevices($user),
                'support_url' => url('/security/help'),
                'review_activity_url' => url('/security/activity'),
                'change_password_url' => url('/password/reset'),
            ];
            
            // Send immediately during development/login troubleshooting.
            // IMPORTANT: Avoid long-running synchronous external calls before this point.
            // Keep this call quick.
            Mail::to($user->email)->send(new GoogleStyleLoginAlert($alertData, $level));
            
            // Update user with notification metadata
            $user->update([
                'last_security_notification_at' => now(),
                'last_risk_level' => $level,
                'last_notification_context' => json_encode($securityContext)
            ]);
            
            // CRITICAL: Also notify admins for high-risk logins
            if ($level === 'critical' && !in_array($user->role, ['admin', 'super_admin'])) {
                $this->notifyAdminsOfSuspiciousActivity($user, $securityContext, $request);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send security alert: ' . $e->getMessage());
        }
    }
    
    /**
     * Get in-app notification message (Google-style banner)
     */
    protected function getInAppNotification($securityContext, $level)
    {
        if ($level === 'none') {
            return null;
        }
        
        $messages = [
            'critical' => [
                'icon' => 'shield-exclamation',
                'color' => 'red',
                'title' => 'Critical Security Alert',
                'message' => 'We detected a sign-in from an unrecognized device or location. Review your security activity immediately.',
                'action' => 'Review Activity'
            ],
            'medium' => [
                'icon' => 'shield-alert',
                'color' => 'yellow',
                'title' => 'New Sign-in Detected',
                'message' => "We noticed a new sign-in from {$securityContext['location']} on a new device. Was this you?",
                'action' => 'Review'
            ],
            'info' => [
                'icon' => 'info-circle',
                'color' => 'blue',
                'title' => 'Sign-in from new device',
                'message' => "Your account was accessed from {$securityContext['location']}. We've saved this device for future logins.",
                'action' => 'Manage Devices'
            ]
        ];
        
        return $messages[$level] ?? null;
    }
    
    /**
     * Get device fingerprint (Google-style)
     */
    protected function getDeviceFingerprint($request)
    {
        $components = [
            $request->userAgent(),
            $this->getDeviceType($request->userAgent()),
            $this->getBrowser($request->userAgent()),
            $request->header('Accept-Language'),
            $request->ip() ?: 'unknown',
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Trust a device for future logins
     */
    protected function trustDevice($user, $deviceFingerprint, $request)
    {
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        
        // Check if device already trusted
        $existingKey = array_search($deviceFingerprint, array_column($trustedDevices, 'fingerprint'));
        
        if ($existingKey !== false) {
            // Update existing device
            $trustedDevices[$existingKey]['last_used'] = now()->toIso8601String();
            $trustedDevices[$existingKey]['trusted_until'] = now()->addDays($this->trustedDeviceDays)->toIso8601String();
        } else {
            // Add new trusted device
            $trustedDevices[] = [
                'fingerprint' => $deviceFingerprint,
                'device_name' => $this->getDeviceName($request),
                'device_type' => $this->getDeviceType($request->userAgent()),
                'browser' => $this->getBrowser($request->userAgent()),
                'location' => $this->getLocationFromIp($request->ip()),
                'first_seen' => now()->toIso8601String(),
                'last_used' => now()->toIso8601String(),
                'trusted_until' => now()->addDays($this->trustedDeviceDays)->toIso8601String(),
            ];
        }
        
        // Limit to 20 trusted devices per user
        if (count($trustedDevices) > 20) {
            $trustedDevices = array_slice($trustedDevices, -20);
        }
        
        $user->update(['trusted_devices' => json_encode($trustedDevices)]);
    }
    
    /**
     * Check if device is trusted
     */
    protected function isTrustedDevice($user, $deviceFingerprint)
    {
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        
        foreach ($trustedDevices as $device) {
            if ($device['fingerprint'] === $deviceFingerprint) {
                // Check if trust hasn't expired
                $trustedUntil = $device['trusted_until'] ?? null;
                if (!$trustedUntil || now()->lt(new \DateTime($trustedUntil))) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Count trusted devices
     */
    protected function countTrustedDevices($user)
    {
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        return count($trustedDevices);
    }
    
    /**
     * Get typical login hours for user (last 30 days)
     */
    protected function getTypicalLoginHours($user)
    {
        $cacheKey = "typical_hours_{$user->id}";
        
        return Cache::remember($cacheKey, 86400, function() use ($user) {
            $hourFunction = config('database.default') === 'sqlite'
                ? "strftime('%H', created_at)"
                : "HOUR(created_at)";

            $hours = \DB::table('security_events')
                ->where('user_identifier', $user->email)
                ->where('event_type', 'successful_login')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw("$hourFunction as hour, COUNT(*) as count")
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->limit(3)
                ->pluck('hour')
                ->toArray();
                
            return !empty($hours) ? $hours : [9, 14, 17]; // Default business hours
        });
    }
    
    /**
     * Calculate approximate distance between two locations
     */
    protected function calculateLocationDistance($location1, $location2)
    {
        // Simple implementation - in production, use geocoding API
        if ($location1 === $location2) return 0;
        return rand(5, 500); // Placeholder - implement actual distance calculation
    }
    
    /**
     * Get detailed device info for email
     */
    protected function getDetailedDeviceInfo($request)
    {
        return [
            'device_name' => $this->getDeviceName($request),
            'device_type' => $this->getDeviceType($request->userAgent()),
            'operating_system' => $this->getOperatingSystem($request->userAgent()),
            'browser' => $this->getBrowser($request->userAgent()),
            'browser_version' => $this->getBrowserVersion($request->userAgent()),
        ];
    }
    
    /**
     * Get device name (e.g., "John's iPhone", "Work Laptop")
     */
    protected function getDeviceName($request)
    {
        $userAgent = $request->userAgent();
        $deviceType = $this->getDeviceType($userAgent);
        
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'iPad')) return 'iPad';
        if (str_contains($userAgent, 'Android')) return 'Android Device';
        if (str_contains($userAgent, 'Mac')) return 'Mac';
        if (str_contains($userAgent, 'Windows')) return 'Windows PC';
        if (str_contains($userAgent, 'Linux')) return 'Linux Computer';
        
        return $deviceType;
    }
    
    /**
     * Get operating system from user agent
     */
    protected function getOperatingSystem($userAgent)
    {
        if (str_contains($userAgent, 'Windows NT 10.0')) return 'Windows 10/11';
        if (str_contains($userAgent, 'Windows NT 6.1')) return 'Windows 7';
        if (str_contains($userAgent, 'Mac OS X')) return 'macOS';
        if (str_contains($userAgent, 'iPhone OS')) return 'iOS';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        return 'Unknown OS';
    }
    
    /**
     * Get browser version
     */
    protected function getBrowserVersion($userAgent)
    {
        // Simple extraction - can be enhanced
        if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Edge\/(\d+)/', $userAgent, $matches)) return $matches[1];
        return 'Latest';
    }
    
    /**
     * Get country code from IP — cached per IP for 1 hour with a hard timeout
     * to prevent this from ever blocking the login request.
     */
    protected function getCountryCode($ip)
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'GH';
        }

        $cacheKey = 'ip_country_' . md5($ip);

        return Cache::remember($cacheKey, 3600, function () use ($ip) {
            try {
                $context = stream_context_create(['http' => ['timeout' => 2]]);
                $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $context);
                if ($response) {
                    $data = json_decode($response, true);
                    return $data['countryCode'] ?? 'GH';
                }
            } catch (\Exception $e) {
                // Fallback silently
            }
            return 'GH';
        });
    }
    
    /**
     * Log security events
     */
    protected function logSecurityEvent($identifier, $eventType, $request, $metadata = [])
    {
        try {
            \DB::table('security_events')->insert([
                'user_identifier' => $identifier,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location' => $this->getLocationFromIp($request->ip()),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to log security event: ' . $e->getMessage());
        }
    }
    
    /**
     * Notify admins of suspicious activity
     */
    protected function notifyAdminsOfSuspiciousActivity($user, $securityContext, $request)
    {
        $admins = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
            ->where('status', 'active')
            ->get();
            
        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new \App\Mail\AdminSuspiciousActivityAlert($user, $securityContext, $request));
        }
    }
    
    /**
     * Get location from IP — result is cached per IP for 1 hour to avoid
     * blocking the login flow with repeated synchronous HTTP calls.
     */
    protected function getLocationFromIp($ip)
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Local Network';
        }

        $cacheKey = 'ip_location_' . md5($ip);

        return Cache::remember($cacheKey, 3600, function () use ($ip) {
            try {
                $context = stream_context_create(['http' => ['timeout' => 3]]);
                $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,region,country", false, $context);

                if ($response) {
                    $data = json_decode($response, true);
                    if ($data && $data['status'] === 'success') {
                        $parts = array_filter([$data['city'] ?? null, $data['region'] ?? null, $data['country'] ?? null]);
                        return implode(', ', $parts) ?: 'Unknown Location';
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('IP geolocation failed: ' . $e->getMessage());
            }

            return 'Location Unknown';
        });
    }
    
    /**
     * Get device type
     */
    protected function getDeviceType($userAgent)
    {
        if (str_contains($userAgent, 'Mobile')) return 'Mobile';
        if (str_contains($userAgent, 'Tablet')) return 'Tablet';
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'iPad')) return 'iPad';
        if (str_contains($userAgent, 'Android')) return 'Android';
        return 'Computer';
    }
    
    /**
     * Get browser name
     */
    protected function getBrowser($userAgent)
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Opera')) return 'Opera';
        return 'Unknown';
    }
    
    protected function redirectBasedOnRole($user)
    {
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'driver':
                return redirect()->route('driver.fuel-mileage.dashboard');
            case 'technician':
                return redirect()->route('technician.dashboard');
            default:
                return redirect('/dashboard');
        }
    }
    
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $this->logSecurityEvent($user->email, 'logout', $request);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
    
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }
    
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $status = Password::sendResetLink($request->only('email'));
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }
    
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                Session::flash('status', 'Password has been reset successfully!');
                $this->sendPasswordChangeNotification($user);
            }
        );
        
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
    
    protected function sendPasswordChangeNotification($user)
    {
        try {
            $data = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'change_time' => now()->format('F j, Y \a\t g:i A'),
                'ip_address' => request()->ip(),
                'location' => $this->getLocationFromIp(request()->ip()),
            ];
            
            Mail::to($user->email)->queue(new \App\Mail\PasswordChangedNotification($data));
        } catch (\Exception $e) {
            \Log::error('Failed to send password change notification: ' . $e->getMessage());
        }
    }
}