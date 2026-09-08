@php
    $portalName = $settings->portal_name ?: 'the portal';
    $companyName = $settings->company_name ?: $portalName;
@endphp

@extends('emails.layout', [
    'emailTitle' => 'Your ' . $portalName . ' invitation',
    'subject' => 'Your ' . $portalName . ' invitation',
    'footerNote' => 'This invitation was sent from ' . $portalName . '.',
])

@section('content')
    <h1 style="font-size: 20px; margin: 0 0 16px;">
        Welcome to {{ $portalName }}
    </h1>

    <p style="font-size: 14px; line-height: 1.6; color: #374151;">
        Hello {{ $recipientName }},
    </p>

    <p style="font-size: 14px; line-height: 1.6; color: #374151;">
        {{ $companyName }} has invited you to access {{ $portalName }}. From the portal you can
        securely view your project documents and stay up to date.
    </p>

    <p style="font-size: 14px; line-height: 1.6; color: #374151;">
        To get started, set your password using the button below.
    </p>

    @include('emails.partials.button', [
        'url' => $setupUrl,
        'label' => 'Set your password',
    ])

    <p class="small" style="font-size: 12px; line-height: 1.5; color: #6b7280;">
        For your security this link will expire after a short time. If it has expired, you can
        request a new password-setup link from the {{ $portalName }} sign-in page.
    </p>

    <div class="panel">
        <p style="margin: 0 0 6px; font-size: 13px; font-weight: bold; color: #111827;">
            {{ $companyName }}
        </p>

        @if ($settings->company_address)
            <p style="margin: 0 0 6px; font-size: 12px; line-height: 1.5; color: #4b5563; white-space: pre-line;">{{ $settings->company_address }}</p>
        @endif

        @if ($settings->company_number)
            <p style="margin: 0; font-size: 12px; color: #4b5563;">
                Company no. {{ $settings->company_number }}
            </p>
        @endif

        @if ($settings->vat_number)
            <p style="margin: 4px 0 0; font-size: 12px; color: #4b5563;">
                VAT no. {{ $settings->vat_number }}
            </p>
        @endif
    </div>

    <p class="small" style="font-size: 12px; line-height: 1.5; color: #6b7280;">
        If you were not expecting this invitation you can safely ignore this email.
    </p>
@endsection
