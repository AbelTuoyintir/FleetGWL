<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Dispatch Note</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .region-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .address-section {
            margin-top: 20px;
        }
        .left-address {
            float: left;
            width: 45%;
        }
        .right-address {
            float: right;
            width: 45%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .ref-section {
            margin-top: 20px;
        }
        .announcement {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 20px 0;
        }
        .content {
            margin-bottom: 20px;
        }
        .affected-areas {
            border: 1px solid #000;
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .affected-areas td {
            padding: 10px;
            vertical-align: top;
            border: 1px solid #000;
        }
        .signature {
            margin-top: 40px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 10px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">GHANA WATER COMPANY LIMITED</div>
        <div class="region-name">{{ strtoupper($region ?? 'HEAD OFFICE') }}</div>
    </div>

    <div class="address-section">
        <div class="left-address">
            <strong>Main Bankers:</strong> Social Security Bank<br>
            Ghana Commercial Bank
        </div>
        <div class="right-address">
            Post Office Box {{ $po_box ?? '163' }}<br>
            {{ $district ?? 'Tema' }} - Ghana<br>
            West Africa<br><br>
            {{ date('d, F, Y') }}
        </div>
        <div class="clear"></div>
    </div>

    <div class="ref-section">
        <strong>My Ref. No.:</strong> {{ $ref_no ?? 'MNT/'.date('Y').'/'.str_pad($maintenance->id, 4, '0', STR_PAD_LEFT) }}<br>
        <strong>Your Ref. No.:</strong> .............................................
    </div>

    <div class="announcement">
        MAINTENANCE DISPATCH ORDER
    </div>

    <div class="content">
        Management of Ghana Water Company Limited - {{ $region ?? 'Tema Region' }}, wishes to inform the designated workshop/service provider that the following vehicle has been dispatched for routine/specific maintenance works.
    </div>

    <table class="affected-areas">
        <tr>
            <td width="50%">
                <strong>VEHICLE DETAILS:</strong><br><br>
                Registration No: {{ $maintenance->vehicle->registration_number }}<br>
                Make & Model: {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}<br>
                Current Mileage: {{ number_format($maintenance->mileage_at_service) }} km<br>
                Driver: {{ $maintenance->driver->name ?? 'N/A' }}
            </td>
            <td width="50%">
                <strong>MAINTENANCE REQUESTED:</strong><br><br>
                Type: {{ ucfirst($maintenance->maintenance_type) }}<br>
                Date: {{ $maintenance->maintenance_date->format('d, M, Y') }}<br>
                Priority: {{ ucfirst($maintenance->priority ?? 'Medium') }}
            </td>
        </tr>
    </table>

    <div class="content">
        <strong>DESCRIPTION OF WORKS:</strong><br>
        {{ $maintenance->description ?? 'Standard servicing as per schedule.' }}
    </div>

    <div class="content">
        Maintenance works should be carried out and a detailed invoice/report submitted upon completion.
    </div>

    <div class="signature">
        Yours faithfully,<br><br><br><br>
        <strong>(FLEET MANAGER)</strong>
    </div>

    <div class="footer">
        <div class="left-address">
            <strong>Head Office:</strong><br>
            Registration office: 28th February Road Near Independence Square<br>
            Tel. No. 233-0302-666781-7<br>
            Website: www.gwcl.com.gh
        </div>
        <div class="right-address">
            <strong>{{ $region ?? 'Tema Region' }}:</strong><br>
            Registration Office: {{ $district ?? 'Tema' }}<br>
            Email: {{ strtolower(str_replace(' ', '.', $region ?? 'tema')) }}@ghanawater.info
        </div>
    </div>
</body>
</html>
