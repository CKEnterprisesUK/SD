@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $companyAddress = $portalSettings->company_address ?: null;
    $companyNumber = $portalSettings->company_number ?: null;
    $contactEmail = $portalSettings->accounts_email ?: 'privacy@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Privacy Notice">
    <p>
        This Privacy Notice explains how {{ $companyName }} ("we", "us", "our") collects, uses, and protects
        personal information when you use SiteDesk as a customer. SiteDesk is our online platform for managing your
        quotes and project documents, and for keeping you informed about the work we carry out for you.
    </p>

    <p>
        We are committed to handling personal data responsibly and in line with the UK General Data Protection
        Regulation (UK GDPR) and the Data Protection Act 2018.
    </p>

    <h2>Who we are</h2>
    <p>
        {{ $companyName }} is the data controller responsible for the personal information described in this notice.
    </p>
    @if ($companyAddress || $companyNumber)
        <ul>
            @if ($companyAddress)
                <li>Registered address: {{ $companyAddress }}</li>
            @endif
            @if ($companyNumber)
                <li>Company number: {{ $companyNumber }}</li>
            @endif
        </ul>
    @endif
    <p>
        If you have any questions about this notice or how we handle your data, contact us at
        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>

    <h2>Information we collect</h2>
    <p>As a customer of SiteDesk, we may hold the following about you:</p>
    <ul>
        <li><strong>Account details</strong> — your name, email address, and account status, where you have login access to view your projects.</li>
        <li><strong>Customer records</strong> — your name or company name, address, contact details, and related notes.</li>
        <li><strong>Quotes and project information</strong> — site addresses, project descriptions, pricing, and documents or photographs relating to the work we quote for or carry out for you, which may include images of your property.</li>
        <li><strong>Documents you share with us</strong> — files you upload or that are shared with you through your project document library.</li>
        <li><strong>Technical data</strong> — information needed to keep you logged in securely, such as session data (see our <a href="{{ route('legal.cookies') }}">Cookies Policy</a>).</li>
    </ul>
    <p>
        We do <strong>not</strong> collect or store bank account details, card numbers, or other payment credentials
        within SiteDesk, and we do not process payments through the platform.
    </p>

    <h2>How we use your information</h2>
    <ul>
        <li>To prepare and share quotes and quote packs with you.</li>
        <li>To manage our relationship with you and keep accurate records of the work we carry out.</li>
        <li>To share and manage your project documents securely (see the customer documents portal below).</li>
        <li>To manage your account, authenticate access, and keep the platform secure.</li>
        <li>To send you service-related communications, such as quote notifications and project updates.</li>
        <li>To meet our legal, accounting, and regulatory obligations.</li>
    </ul>

    <h2>Legal basis for processing</h2>
    <p>
        We process personal data where it is necessary to perform a contract with you, to comply with a legal
        obligation, or in our legitimate interests in delivering, securing, and improving our services.
    </p>

    <h2>The customer documents portal</h2>
    <p>
        SiteDesk includes a project document library that lets us share documents with you securely. Access is
        controlled so that you can see only the projects that belong to you. Document and folder activity is logged
        for security and audit purposes.
    </p>

    <h2>Sharing your information</h2>
    <p>
        We do not sell your personal information. We may share it with trusted service providers who help us run
        the platform, such as our hosting provider and email delivery provider. We may also disclose information
        where required by law or to establish, exercise, or defend legal claims.
    </p>

    <h2>Where your data is processed</h2>
    <p>
        Some of our service providers may process data outside the UK. Where data is transferred internationally,
        we take steps to ensure it remains protected in line with UK data protection law.
    </p>

    <h2>Data retention</h2>
    <p>
        We keep personal information for as long as needed to provide our services and maintain our customer and
        project records, and to meet legal and accounting obligations (for example, retaining quote and project
        records for the period required by law). When information is no longer needed, we take steps to delete or
        anonymise it.
    </p>

    <h2>Your rights</h2>
    <p>
        Subject to certain conditions, you have the right to access, correct, or delete your personal data, to
        object to or restrict certain processing, and to request data portability. To exercise these rights,
        contact us at <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>. You also have the right to
        complain to the UK Information Commissioner's Office (ICO) at <a href="https://ico.org.uk" target="_blank" rel="noopener noreferrer">ico.org.uk</a>.
    </p>

    <h2>Changes to this notice</h2>
    <p>
        We may update this Privacy Notice from time to time. Any changes will be posted on this page with an
        updated revision date.
    </p>
</x-layouts.legal>
