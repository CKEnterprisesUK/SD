@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $contactEmail = $portalSettings->accounts_email ?: 'support@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Terms of Service">
    <p>
        These Terms of Service ("Terms") govern your access to and use of SiteDesk, provided by
        {{ $companyName }} ("we", "us", "our"). By accessing or using the service, you agree to be bound by these Terms.
    </p>

    <h2>Using the service</h2>
    <p>
        You must use SiteDesk in accordance with these Terms and all applicable laws. You are responsible for
        maintaining the confidentiality of your account credentials and for all activity under your account.
    </p>

    <h2>Accounts</h2>
    <ul>
        <li>You must provide accurate and complete information when creating an account.</li>
        <li>You are responsible for keeping your login details secure.</li>
        <li>You must notify us promptly of any unauthorised use of your account.</li>
    </ul>

    <h2>Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the service for any unlawful or fraudulent purpose.</li>
        <li>Attempt to gain unauthorised access to the platform or its systems.</li>
        <li>Interfere with or disrupt the integrity or performance of the service.</li>
        <li>Upload content that infringes the rights of others.</li>
    </ul>

    <h2>Your content</h2>
    <p>
        You retain ownership of the data and documents you upload. You grant us the rights necessary to host,
        process, and display that content solely to provide the service to you.
    </p>

    <h2>Availability</h2>
    <p>
        We aim to keep SiteDesk available and reliable, but we do not guarantee uninterrupted access. We may
        suspend the service for maintenance or updates.
    </p>

    <h2>Limitation of liability</h2>
    <p>
        To the extent permitted by law, {{ $companyName }} is not liable for any indirect, incidental, or
        consequential loss arising from your use of the service.
    </p>

    <h2>Termination</h2>
    <p>
        We may suspend or terminate access if these Terms are breached. You may stop using the service at any time.
    </p>

    <h2>Changes to these Terms</h2>
    <p>
        We may revise these Terms from time to time. Continued use of the service after changes take effect
        constitutes acceptance of the updated Terms.
    </p>

    <h2>Contact</h2>
    <p>
        Questions about these Terms can be sent to <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>
</x-layouts.legal>
