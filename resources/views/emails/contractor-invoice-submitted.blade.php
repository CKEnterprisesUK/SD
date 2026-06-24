<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contractor invoice submitted</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 16px;">
        Contractor invoice submitted
    </h1>

    @if ($recipientType === 'contractor')
        <p>
            Your contractor invoice has been submitted using SiteDesk.
        </p>
    @else
        <p>
            A contractor invoice has been submitted using SiteDesk.
        </p>
    @endif

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin-top: 16px;">
        <tr>
            <td><strong>Invoice number:</strong></td>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Contractor:</strong></td>
            <td>{{ $invoice->supplier_name }}</td>
        </tr>
        <tr>
            <td><strong>Invoice date:</strong></td>
            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Week commencing:</strong></td>
            <td>{{ $invoice->week_commencing->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Days submitted:</strong></td>
            <td>{{ $invoice->days_worked }}</td>
        </tr>
        <tr>
            <td><strong>Total due:</strong></td>
            <td>£{{ $invoice->total }}</td>
        </tr>
    </table>

    @if ($invoice->day_rate_overridden)
        <p style="margin-top: 16px; padding: 10px; border: 1px solid #92400e; background: #fffbeb; color: #78350f;">
            The contractor manually overrode the default day rate on this invoice.
        </p>
    @endif

    <p style="margin-top: 20px;">
        The PDF copy is attached.
    </p>

    <p style="margin-top: 20px; font-size: 12px; color: #4b5563;">
        This invoice was submitted by {{ $invoice->supplier_name }} to {{ $invoice->customer_name ?: 'the building firm' }}
        using SiteDesk — A CK Enterprises Product. This is not a VAT invoice. VAT has not been charged.
    </p>
</body>
</html>