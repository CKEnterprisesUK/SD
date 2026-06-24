@extends('emails.layout', [
    'emailTitle' => 'Invoice returned for changes',
    'emailLabel' => 'Action required',
    'emailIntro' => 'Your invoice has been reviewed and returned for changes. Please review the accounts comment, edit the invoice and resubmit it.',
    'footerNote' => 'This email relates to an invoice returned for changes through SiteDesk.',
])

@section('content')
    @include('emails.partials.detail-table', [
        'rows' => [
            'Invoice number' => e($invoice->invoice_number),
            'Week commencing' => e($invoice->week_commencing->format('d M Y')),
            'Total due' => '£' . e($invoice->total),
        ],
    ])

    @if ($invoice->review_comment)
        <div style="border: 1px solid #991b1b; background: #fef2f2; color: #7f1d1d; padding: 12px; font-size: 13px; line-height: 1.5; margin: 18px 0;">
            <strong>Accounts comment</strong>

            <div style="white-space: pre-line; margin-top: 8px;">
                {{ $invoice->review_comment }}
            </div>
        </div>
    @endif

    @include('emails.partials.button', [
        'url' => $editUrl,
        'label' => 'Edit and resubmit invoice',
    ])

    <p style="font-size: 13px; line-height: 1.5; color: #4b5563; margin: 18px 0 0 0;">
        This invoice will remain returned until it is edited and resubmitted through SiteDesk.
    </p>
@endsection