<?php
// app/Http/Controllers/SecurityController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SecurityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Show security activity page (Google-style)
     */
    public function activity()
    {
        $user = Auth::user();
        
        // Get recent security events
        $events = DB::table('security_events')
            ->where('user_identifier', $user->email)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Get security statistics
        $stats = [
            'total_logins' => DB::table('security_events')
                ->where('user_identifier', $user->email)
                ->where('event_type', 'successful_login')
                ->count(),
            'unique_devices' => count(json_decode($user->trusted_devices ?? '[]', true)),
            'suspicious_events' => DB::table('security_events')
                ->where('user_identifier', $user->email)
                ->whereRaw('JSON_EXTRACT(metadata, "$.security_score") < 70')
                ->count(),
            'last_activity' => $user->last_login_at,
        ];
        
        return view('security.activity', compact('events', 'stats'));
    }
    
    /**
     * Show trusted devices management page
     */
    public function devices()
    {
        $user = Auth::user();
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        $currentDeviceFingerprint = $this->getCurrentDeviceFingerprint(request());
        
        return view('security.devices', compact('trustedDevices', 'currentDeviceFingerprint'));
    }
    
    /**
     * Remove a trusted device
     */
    public function removeDevice(Request $request, $deviceId)
    {
        $user = Auth::user();
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        
        // Remove device by index or fingerprint
        if (isset($trustedDevices[$deviceId])) {
            unset($trustedDevices[$deviceId]);
            $user->update(['trusted_devices' => json_encode(array_values($trustedDevices))]);
            
            return response()->json(['success' => true, 'message' => 'Device removed successfully']);
        }
        
        return response()->json(['success' => false, 'message' => 'Device not found'], 404);
    }
    
    /**
     * Trust a device
     */
    public function trustDevice(Request $request, $deviceId)
    {
        $user = Auth::user();
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        
        if (isset($trustedDevices[$deviceId])) {
            $trustedDevices[$deviceId]['trusted_until'] = now()->addDays(30)->toIso8601String();
            $user->update(['trusted_devices' => json_encode($trustedDevices)]);
            
            return response()->json(['success' => true, 'message' => 'Device trusted successfully']);
        }
        
        return response()->json(['success' => false, 'message' => 'Device not found'], 404);
    }
    
    /**
     * Untrust a device
     */
    public function untrustDevice(Request $request, $deviceId)
    {
        $user = Auth::user();
        $trustedDevices = json_decode($user->trusted_devices ?? '[]', true);
        
        if (isset($trustedDevices[$deviceId])) {
            unset($trustedDevices[$deviceId]['trusted_until']);
            $user->update(['trusted_devices' => json_encode($trustedDevices)]);
            
            return response()->json(['success' => true, 'message' => 'Device untrusted successfully']);
        }
        
        return response()->json(['success' => false, 'message' => 'Device not found'], 404);
    }
    
    /**
     * Show security settings page
     */
    public function settings()
    {
        $user = Auth::user();
        $notificationPrefs = json_decode($user->notification_preferences ?? '[]', true);
        
        return view('security.settings', compact('notificationPrefs'));
    }
    
    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'critical_only' => 'boolean',
            'daily_digest' => 'boolean',
            'sms_alerts' => 'boolean',
            'phone_number' => 'required_if:sms_alerts,true|nullable',
        ]);
        
        $user = Auth::user();
        $prefs = [
            'email_notifications' => $request->boolean('email_notifications'),
            'critical_only' => $request->boolean('critical_only'),
            'daily_digest' => $request->boolean('daily_digest'),
            'sms_alerts' => $request->boolean('sms_alerts'),
            'phone_number' => $request->input('phone_number'),
            'updated_at' => now(),
        ];
        
        $user->update(['notification_preferences' => json_encode($prefs)]);
        
        return back()->with('success', 'Notification settings updated successfully');
    }
    
    /**
     * Get recent security events (AJAX)
     */
    public function recentEvents(Request $request)
    {
        $user = Auth::user();
        
        $events = DB::table('security_events')
            ->where('user_identifier', $user->email)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json(['events' => $events]);
    }
    
    /**
     * Mark suspicious event as reviewed
     */
    public function markAsReviewed(Request $request, $eventId)
    {
        $user = Auth::user();
        
        DB::table('security_events')
            ->where('id', $eventId)
            ->where('user_identifier', $user->email)
            ->update([
                'metadata->reviewed' => true,
                'metadata->reviewed_at' => now()->toDateTimeString(),
            ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Setup Two-Factor Authentication
     */
    public function setupTwoFactor()
    {
        $user = Auth::user();
        
        // Generate secret key for Google Authenticator
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();
        
        // Store secret in session temporarily
        session(['2fa_secret' => $secret]);
        
        $qrCode = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );
        
        return view('security.2fa-setup', compact('qrCode', 'secret'));
    }
    
    /**
     * Enable Two-Factor Authentication
     */
    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|string|size:6',
        ]);
        
        $google2fa = app('pragmarx.google2fa');
        $secret = session('2fa_secret');
        
        $valid = $google2fa->verifyKey($secret, $request->one_time_password);
        
        if ($valid) {
            $user = Auth::user();
            $user->update([
                'two_factor_secret' => encrypt($secret),
                'two_factor_enabled' => true,
            ]);
            
            // Generate recovery codes
            $recoveryCodes = $this->generateRecoveryCodes();
            $user->update(['recovery_codes' => encrypt(json_encode($recoveryCodes))]);
            
            session()->forget('2fa_secret');
            
            return redirect()->route('2fa.recovery-codes')->with('success', 'Two-factor authentication enabled successfully');
        }
        
        return back()->withErrors(['one_time_password' => 'Invalid verification code']);
    }
    
    /**
     * Disable Two-Factor Authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'recovery_codes' => null,
        ]);
        
        return back()->with('success', 'Two-factor authentication disabled');
    }
    
    /**
     * Show recovery codes
     */
    public function showRecoveryCodes()
    {
        $user = Auth::user();
        $recoveryCodes = json_decode(decrypt($user->recovery_codes), true);
        
        return view('security.recovery-codes', compact('recoveryCodes'));
    }
    
    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes()
    {
        $user = Auth::user();
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->update(['recovery_codes' => encrypt(json_encode($recoveryCodes))]);
        
        return redirect()->route('2fa.recovery-codes')->with('success', 'New recovery codes generated');
    }
    
    /**
     * Generate recovery codes for 2FA
     */
    private function generateRecoveryCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            // SECURITY: Use cryptographically secure random string for recovery codes
            $codes[] = strtoupper(\Illuminate\Support\Str::random(8));
        }
        return $codes;
    }
    
    /**
     * Get current device fingerprint
     */
    private function getCurrentDeviceFingerprint($request)
    {
        $components = [
            $request->userAgent(),
            $request->ip(),
            $request->header('Accept-Language'),
        ];
        
        return hash('sha256', implode('|', $components));
    }
}