@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $contactEmail = $portalSettings->accounts_email ?: 'privacy@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Cookies Policy">
    <p>
        This Cookies Policy explains how {{ $companyName }} uses cookies when you use SiteDesk. It should be read
        alongside our <a href="{{ route('legal.privacy') }}">Privacy Notice</a>.
    </p>

    <h2>What are cookies?</h2>
    <p>
        Cookies are small text files placed on your device when you visit a website. They allow the site to
        function correctly and to remember information about your session.
    </p>

    <h2>How we use cookies</h2>
    <p>
        SiteDesk uses only <strong>essential cookies</strong> that are strictly necessary for the platform to work.
        We do not use advertising cookies, and we do not use third-party analytics or tracking cookies.
    </p>

    <h2>The cookies we use</h2>
    <ul>
        <li><strong>Session cookie</strong> — keeps you signed in and maintains your session while you use the platform.</li>
        <li><strong>Security (CSRF) cookie</strong> — protects forms and requests against cross-site request forgery.</li>
        <li><strong>"Remember me" cookie</strong> — set only if you choose the "remember me" option at login, so you stay signed in on your device.</li>
    </ul>
    <p>
        These cookies are essential to providing a secure, working service. Because they are strictly necessary,
        they do not require consent, but you can still control them as described below.
    </p>

    <h2>Managing cookies</h2>
    <p>
        Most browsers let you control cookies through their settings, including blocking or deleting them. Please
        note that blocking the essential cookies above will prevent you from logging in and using SiteDesk.
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
