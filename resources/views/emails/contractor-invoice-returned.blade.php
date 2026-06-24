<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice returned for changes</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 16px;">
        Invoice returned for changes
    </h1>

    <p>
        Your contractor invoice has been reviewed and returned for changes in SiteDesk.
    </p>

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin-top: 16px;">
        <tr>
            <td><strong>Invoice number:</strong></td>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Week commencing:</strong></td>
            <td>{{ $invoice->week_commencing->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td>£{{ $invoice->total }}</td>
        </tr>
    </table>

    @if ($invoice->review_comment)
        <div style="margin-top: 20px; padding: 12px; border: 1px solid #991b1b; background: #fef2f2; color: #7f1d1d;">
            <strong>Accounts comment:</strong>
            <p style="white-space: pre-line; margin-bottom: 0;">{{ $invoice->review_comment }}</p>
        </div>
    @endif

    <p style="margin-top: 20px;">
        Please log in to SiteDesk, edit the invoice and resubmit it for review.
    </p>

    <p style="margin-top: 20px;">
        <a href="{{ $editUrl }}" style="background: #111827; color: #ffffff; padding: 10px 14px; text-decoration: none; display: inline-block;">
            Edit invoice
        </a>
    </p>

    <p style="margin-top: 24px; font-size: 12px; color: #4b5563;">
        Powered by SiteDesk — A CK Enterprises Group Product.
    </p>
</body>
</html>