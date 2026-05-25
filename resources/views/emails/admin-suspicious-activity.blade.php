Suspicious Activity Alert

User: {{ $user->name }} ({{ $user->email }})
Security Score: {{ $context['score'] }}
Risk Factors: {{ implode(', ', $context['risk_factors']) }}
Location: {{ $context['location'] }}
IP Address: {{ $ip }}
Time: {{ now() }}

Please review this activity immediately.

