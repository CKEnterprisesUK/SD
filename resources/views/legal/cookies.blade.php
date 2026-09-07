@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $contactEmail = $portalSettings->accounts_email ?: 'privacy@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Cookies Policy">
    <p>
        This Cookies Policy explains how {{ $companyName }} uses cookies and similar technologies when you use
        SiteDesk. It should be read alongside our <a href="{{ route('legal.privacy') }}">Privacy Notice</a>.
    </p>

    <h2>What are cookies?</h2>
    <p>
        Cookies are small text files placed on your device when you visit a website. They help the site function
        correctly and remember your preferences.
    </p>

    <h2>Cookies we use</h2>
    <ul>
        <li><strong>Essential cookies</strong> — required for the platform to work, including secure login and session management.</li>
        <li><strong>Preference cookies</strong> — remember settings such as your display choices.</li>
        <li><strong>Analytics cookies</strong> — help us understand how the service is used so we can improve it.</li>
    </ul>

    <h2>Managing cookies</h2>
    <p>
        Most browsers let you control cookies through their settings, including blocking or deleting them. Please
        note that disabling essential cookies may affect how SiteDesk functions.
    </p>

    <h2>Changes to this policy</h2>
    <p>
        We may update this Cookies Policy from time to time. Any changes will be posted on this page with an
        updated revision date.
    </p>

    <h2>Contact</h2>
    <p>
        If you have questions about our use of cookies, contact us at
        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>
</x-layouts.legal>
