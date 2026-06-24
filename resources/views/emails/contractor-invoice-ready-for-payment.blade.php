<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice ready for payment</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 16px;">
        Invoice ready for payment
    </h1>

    @if ($recipientType === 'contractor')
        <p>
            Your contractor invoice has been reviewed and marked as ready for payment.
        </p>
    @else
        <p>
            A contractor invoice has been reviewed and marked as ready for payment in SiteDesk.
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
            <td><strong>Week commencing:</strong></td>
            <td>{{ $invoice->week_commencing->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Total due:</strong></td>
            <td>£{{ $invoice->total }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">
        The PDF copy is attached.
    </p>

    <p style="margin-top: 24px; font-size: 12px; color: #4b5563;">
        This invoice was submitted by {{ $invoice->supplier_name }} to {{ $invoice->customer_name ?: 'the building firm' }}
        using SiteDesk — A CK Enterprises Group Product. This is not a VAT invoice. VAT has not been charged.
    </p>
</body>
</html>