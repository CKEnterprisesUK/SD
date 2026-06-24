@extends('emails.layout', [
    'emailTitle' => 'Invoice ready for payment',
    'emailLabel' => 'Invoice reviewed',
    'emailIntro' => $recipientType === 'contractor'
        ? 'Your invoice has been reviewed and marked as ready for payment.'
        : 'A contractor invoice has been reviewed and marked as ready for payment in SiteDesk.',
    'footerNote' => 'This email relates to an invoice reviewed through SiteDesk.',
])

@section('content')
    @include('emails.partials.detail-table', [
        'rows' => [
            'Invoice number' => e($invoice->invoice_number),
            'Contractor' => e($invoice->supplier_name),
            'Week commencing' => e($invoice->week_commencing->format('d M Y')),
            'Total due' => '£' . e($invoice->total),
        ],
    ])

    <div style="border: 1px solid #15803d; background: #f0fdf4; color: #166534; padding: 12px; font-size: 13px; line-height: 1.5; margin: 18px 0;">
        This invoice has been marked as ready for payment.
    </div>

    <p style="font-size: 14px; line-height: 1.6; color: #374151; margin: 18px 0 0 0;">
        A PDF copy of the invoice is attached.
    </p>

    <p style="font-size: 12px; line-height: 1.5; color: #4b5563; margin: 20px 0 0 0;">
        This invoice was submitted by {{ $invoice->supplier_name }} to {{ $invoice->customer_name ?: 'the building firm' }}.
        This is not a VAT invoice. VAT has not been charged.
    </p>
@endsection