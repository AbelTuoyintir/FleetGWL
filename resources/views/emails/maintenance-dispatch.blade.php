<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Dispatch Notification</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
        <h2 style="color: #2563eb; text-align: center;">Maintenance Dispatch Notification</h2>

        <p>Dear {{ $maintenance->driver->name ?? 'Driver' }},</p>

        <p>Your maintenance request for vehicle <strong>{{ $maintenance->vehicle->registration_number }}</strong> has been approved and dispatched.</p>

        <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; font-size: 16px;">Job Details:</h3>
            <ul style="list-style: none; padding: 0;">
                <li><strong>Type:</strong> {{ ucfirst($maintenance->maintenance_type) }}</li>
                <li><strong>Date:</strong> {{ $maintenance->maintenance_date->format('d, M, Y') }}</li>
                <li><strong>Status:</strong> Dispatched / In Workshop</li>
            </ul>
        </div>

        <p>Please proceed to the workshop as scheduled or contact the fleet manager for further instructions.</p>

        <p>A copy of the dispatch note is attached to this email.</p>

        <p style="margin-top: 30px;">Best regards,<br>
        <strong>Fleet Management Team</strong><br>
        Ghana Water Company Limited</p>
    </div>
</body>
</html>
