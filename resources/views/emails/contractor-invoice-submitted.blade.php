@extends('emails.layout', [
    'emailTitle' => 'Invoice submitted',
    'emailLabel' => 'Invoice notification',
    'emailIntro' => $recipientType === 'contractor'
        ? 'Your invoice has been submitted through SiteDesk and sent for review.'
        : 'A contractor invoice has been submitted through SiteDesk and is ready for review.',
    'footerNote' => 'This email relates to an invoice submitted through SiteDesk.',
])

@section('content')
    @include('emails.partials.detail-table', [
        'rows' => [
            'Invoice number' => e($invoice->invoice_number),
            'Contractor' => e($invoice->supplier_name),
            'Invoice date' => e($invoice->invoice_date->format('d M Y')),
            'Week commencing' => e($invoice->week_commencing->format('d M Y')),
            'Days submitted' => e($invoice->days_worked),
            'Total due' => '£' . e($invoice->total),
        ],
    ])

    @if ($invoice->day_rate_overridden)
        <div style="border: 1px solid #92400e; background: #fffbeb; color: #78350f; padding: 12px; font-size: 13px; line-height: 1.5; margin: 18px 0;">
            <strong>Day rate changed:</strong>
            The contractor manually overrode the default day rate on this invoice.
        </div>
    @endif

    <p style="font-size: 14px; line-height: 1.6; color: #374151; margin: 18px 0 0 0;">
        A PDF copy of the invoice is attached.
    </p>

    <p style="font-size: 12px; line-height: 1.5; color: #4b5563; margin: 20px 0 0 0;">
        This invoice was submitted by {{ $invoice->supplier_name }} to {{ $invoice->customer_name ?: 'the building firm' }}.
        This is not a VAT invoice. VAT has not been charged.
    </p>
@endsection