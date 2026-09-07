@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $contactEmail = $portalSettings->accounts_email ?: 'privacy@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Privacy Notice">
    <p>
        This Privacy Notice explains how {{ $companyName }} ("we", "us", "our") collects, uses, and protects
        personal information when you use SiteDesk and related services. We are committed to handling your data
        responsibly and in line with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.
    </p>

    <h2>Who we are</h2>
    <p>
        {{ $companyName }} is the data controller responsible for your personal information. If you have any
        questions about this notice, contact us at <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>

    <h2>Information we collect</h2>
    <ul>
        <li>Account details such as your name, email address, and role.</li>
        <li>Business information you provide, including customer, quote, and invoice records.</li>
        <li>Documents and files you upload to the platform.</li>
        <li>Technical data such as IP address, browser type, and usage activity.</li>
    </ul>

    <h2>How we use your information</h2>
    <ul>
        <li>To provide, maintain, and improve the SiteDesk service.</li>
        <li>To manage your account and authenticate access.</li>
        <li>To send service-related communications, such as invoice and quote notifications.</li>
        <li>To meet our legal and regulatory obligations.</li>
    </ul>

    <h2>Legal basis for processing</h2>
    <p>
        We process personal data where it is necessary to perform our contract with you, to comply with a legal
        obligation, or on the basis of our legitimate interests in operating and securing the service.
    </p>

    <h2>Sharing your information</h2>
    <p>
        We do not sell your personal information. We may share it with trusted service providers who help us run
        the platform (for example, hosting and email delivery), and where required by law.
    </p>

    <h2>Data retention</h2>
    <p>
        We keep personal information for as long as your account is active or as needed to provide the service,
        meet legal obligations, resolve disputes, and enforce our agreements.
    </p>

    <h2>Your rights</h2>
    <p>
        You have the right to access, correct, or delete your personal data, to object to or restrict certain
        processing, and to request data portability. To exercise these rights, contact us at
        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>

    <h2>Changes to this notice</h2>
    <p>
        We may update this Privacy Notice from time to time. Any changes will be posted on this page with an
        updated revision date.
    </p>
</x-layouts.legal>
