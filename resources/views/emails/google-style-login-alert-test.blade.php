{{-- minimal template to test mail rendering quickly --}}
<div>
    <p>Security Alert</p>
    <p><strong>{{ $level ?? 'info' }}</strong></p>
    <p>User: {{ $data['user_email'] ?? 'unknown' }}</p>
</div>

