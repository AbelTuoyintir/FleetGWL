{{-- resources/views/emails/google-style-login-alert.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Security Alert - {{ config('app.name') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .button { width: 100% !important; }
            .responsive-table { width: 100% !important; }
            .stack-cell { display: block !important; width: 100% !important; text-align: left !important; }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #202124;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e8eaed;
        }
        .logo {
            font-size: 20px;
            font-weight: 500;
            color: #1a73e8;
            text-decoration: none;
        }
        .alert-banner {
            padding: 16px 24px;
            margin: 20px 24px;
            border-radius: 8px;
        }
        .alert-critical {
            background-color: #fce8e6;
            border-left: 4px solid #d93025;
        }
        .alert-medium {
            background-color: #fef7e0;
            border-left: 4px solid #f9ab00;
        }
        .alert-info {
            background-color: #e8f0fe;
            border-left: 4px solid #1a73e8;
        }
        .title {
            font-size: 20px;
            font-weight: 500;
            margin: 0 0 8px;
            color: #202124;
        }
        .content {
            padding: 0 24px 24px;
        }
        .device-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            border: 1px solid #e8eaed;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e8eaed;
        }
        .info-label {
            font-weight: 500;
            color: #5f6368;
        }
        .info-value {
            color: #202124;
            font-weight: 400;
        }
        .button {
            display: inline-block;
            padding: 10px 24px;
            background-color: #1a73e8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin: 8px 8px 8px 0;
        }
        .button-secondary {
            background-color: #fff;
            color: #1a73e8;
            border: 1px solid #dadce0;
        }
        .risk-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .risk-high { background-color: #fce8e6; color: #c5221f; }
        .risk-medium { background-color: #fef7e0; color: #e37400; }
        .risk-low { background-color: #e8f0fe; color: #1a73e8; }
        .footer {
            padding: 20px 24px;
            background-color: #f8f9fa;
            border-top: 1px solid #e8eaed;
            font-size: 12px;
            color: #5f6368;
            text-align: center;
        }
        .security-score {
            display: inline-block;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#1a73e8 0% {{ $alertData['security_score'] }}%, #e8eaed {{ $alertData['security_score'] }}% 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .score-inner {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }
    </style>
</head>
<body style="margin: 0; padding: 20px; background-color: #f4f4f4;">
    <div class="container" style="margin: 0 auto; max-width: 560px;">
        <!-- Header -->
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo" style="text-decoration: none;">
                <strong>{{ config('app.name') }}</strong>
            </a>
            <span style="float: right; font-size: 12px; color: #5f6368;">Security Alert</span>
        </div>
        
        <!-- Alert Banner -->
        @if($level === 'critical')
        <div class="alert-banner alert-critical">
            <div style="font-size: 28px; margin-bottom: 8px;">⚠️</div>
            <div class="title">Critical security alert</div>
            <div style="color: #5f6368;">We detected a sign-in that may be suspicious. Please review this activity.</div>
        </div>
        @elseif($level === 'medium')
        <div class="alert-banner alert-medium">
            <div style="font-size: 28px; margin-bottom: 8px;">🔐</div>
            <div class="title">New sign-in detected</div>
            <div style="color: #5f6368;">We noticed a new sign-in to your account. Was this you?</div>
        </div>
        @else
        <div class="alert-banner alert-info">
            <div style="font-size: 28px; margin-bottom: 8px;">ℹ️</div>
            <div class="title">Sign-in from new device</div>
            <div style="color: #5f6368;">Your account was accessed from a new device or location.</div>
        </div>
        @endif
        
        <!-- Content -->
        <div class="content">
            <p style="margin-bottom: 16px;">Hi <strong>{{ $alertData['user_name'] }}</strong>,</p>
            
            <!-- Security Score (Google-style) -->
            <div style="text-align: center; margin: 20px 0;">
                <div class="security-score" style="margin: 0 auto;">
                    <div class="score-inner">{{ $alertData['security_score'] }}</div>
                </div>
                <div style="margin-top: 8px; font-size: 12px; color: #5f6368;">Security Confidence Score</div>
            </div>
            
            <!-- Sign-in Details -->
            <div class="device-card">
                <div style="font-weight: 500; margin-bottom: 12px;">📱 Sign-in details</div>
                <div class="info-row">
                    <span class="info-label">Date & time</span>
                    <span class="info-value">{{ $alertData['day_of_week'] }}, {{ $alertData['login_date'] }} at {{ $alertData['login_time'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value">{{ $alertData['location'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Device</span>
                    <span class="info-value">{{ $alertData['device_info']['device_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Browser & OS</span>
                    <span class="info-value">{{ $alertData['device_info']['browser'] }} on {{ $alertData['device_info']['operating_system'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">IP Address</span>
                    <span class="info-value">{{ $alertData['ip_address'] }}</span>
                </div>
            </div>
            
            <!-- Risk Factors -->
            @if(!empty($alertData['risk_factors']))
            <div style="margin: 16px 0;">
                <div style="font-weight: 500; margin-bottom: 8px;">⚠️ Risk factors detected</div>
                @foreach($alertData['risk_factors'] as $factor)
                    @php
                        $riskText = [
                            'new_device' => 'Sign-in from a new device',
                            'location_changed' => 'Location changed from last sign-in',
                            'location_changed_drastic' => 'Sign-in from a faraway location',
                            'unusual_time' => 'Unusual sign-in time',
                            'multiple_failures' => 'Multiple failed attempts before this sign-in',
                            'browser_changed' => 'Different browser than usual',
                            'high_risk_location' => 'Sign-in from high-risk location',
                        ][$factor] ?? ucfirst(str_replace('_', ' ', $factor));
                    @endphp
                    <div class="risk-badge risk-medium">{{ $riskText }}</div>
                @endforeach
            </div>
            @endif
            
            <!-- Action Buttons -->
            <div style="margin: 24px 0;">
                @if($alertData['action_required'])
                <a href="{{ $alertData['review_activity_url'] }}" class="button">Review security activity</a>
                <a href="{{ $alertData['change_password_url'] }}" class="button button-secondary">Change password</a>
                @else
                <a href="{{ $alertData['review_activity_url'] }}" class="button">Yes, that was me</a>
                <a href="{{ $alertData['change_password_url'] }}" class="button button-secondary">No, secure my account</a>
                @endif
            </div>
            
            <!-- Trusted Devices Info -->
            <div style="font-size: 13px; color: #5f6368; padding: 12px 0; border-top: 1px solid #e8eaed; margin-top: 16px;">
                <div style="margin-bottom: 8px;">
                    <span>🔒 You have {{ $alertData['trusted_devices_count'] }} trusted device(s)</span>
                    <a href="{{ $alertData['review_activity_url'] }}" style="color: #1a73e8; text-decoration: none; float: right;">Manage devices →</a>
                </div>
                <div style="clear: both;"></div>
                @if($alertData['is_new_device'])
                <div style="background-color: #e8f0fe; padding: 8px; border-radius: 4px; margin-top: 8px;">
                    ℹ️ This device has been added to your trusted devices. You can manage or remove it anytime.
                </div>
                @endif
            </div>
            
            <!-- Help Text -->
            <div style="font-size: 12px; color: #5f6368; margin-top: 20px;">
                <p style="margin: 0;">This email was sent to {{ $alertData['user_email'] }} because someone signed in to your account. If you didn't recognize this sign-in, <a href="{{ $alertData['change_password_url'] }}" style="color: #1a73e8;">reset your password immediately</a>.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="margin-bottom: 12px;">
                <a href="{{ config('app.url') }}/security/help" style="color: #5f6368; text-decoration: none; margin: 0 8px;">Help Center</a>
                <a href="{{ config('app.url') }}/security/activity" style="color: #5f6368; text-decoration: none; margin: 0 8px;">Security Activity</a>
                <a href="{{ config('app.url') }}/settings/notifications" style="color: #5f6368; text-decoration: none; margin: 0 8px;">Notification Settings</a>
            </div>
            <div>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                This is an automated security notification. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>