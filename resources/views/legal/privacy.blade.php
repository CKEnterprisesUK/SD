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
        personal information when you use SiteDesk. SiteDesk is a construction quoting and project management
        platform used by our team to prepare quotes, manage customer and contractor records, process contractor
        invoices, and share project documents.
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

    <h2>Who this notice applies to</h2>
    <p>SiteDesk is used by different types of people, and we handle data about each:</p>
    <ul>
        <li><strong>Staff users (admins)</strong> — members of our team who log in to operate the platform.</li>
        <li><strong>Contractors</strong> — subcontractors who are invited to log in to submit and view their invoices.</li>
        <li><strong>Customers</strong> — the businesses and individuals we prepare quotes and carry out work for, including their nominated contacts.</li>
    </ul>

    <h2>Information we collect</h2>
    <p>Depending on your relationship with us, we may hold the following:</p>
    <ul>
        <li><strong>Account details</strong> — name, email address, role, and account status for anyone with a login.</li>
        <li><strong>Customer records</strong> — customer or company name, address, contact names, email addresses, phone numbers, and related notes.</li>
        <li><strong>Contractor records</strong> — name, email address, phone number, address, company name, and agreed day rate.</li>
        <li><strong>Quotes and surveys</strong> — site addresses, survey notes, project descriptions, pricing line items, and uploaded site photos or documents, which may include images of properties.</li>
        <li><strong>Contractor invoices</strong> — supplier name, contact details and address, days worked, rates, line items, notes, a submission confirmation record, and the IP address recorded at the time an invoice is submitted.</li>
        <li><strong>Technical data</strong> — information needed to keep you logged in securely, such as session data (see our <a href="{{ route('legal.cookies') }}">Cookies Policy</a>).</li>
    </ul>
    <p>
        We do <strong>not</strong> collect or store bank account details, card numbers, or other payment credentials
        within SiteDesk, and we do not process payments through the platform.
    </p>

    <h2>How we use your information</h2>
    <ul>
        <li>To prepare, price, and issue quotes and quote packs for customers.</li>
        <li>To manage customer and contractor relationships and records.</li>
        <li>To receive, review, and process contractor invoices.</li>
        <li>To share and manage project documents with the relevant people (see the customer documents portal below).</li>
        <li>To manage user accounts, authenticate access, and keep the platform secure.</li>
        <li>To send service-related communications, such as invoice and quote notifications.</li>
        <li>To meet our legal, accounting, and regulatory obligations.</li>
    </ul>

    <h2>Legal basis for processing</h2>
    <p>
        We process personal data where it is necessary to perform a contract with you or your organisation, to
        comply with a legal obligation, or in our legitimate interests in operating, securing, and improving our
        quoting and project management services.
    </p>

    <h2>Artificial intelligence (AI) features</h2>
    <p>
        SiteDesk includes optional AI tools that help our team draft quote pricing estimates and customer-facing
        wording. When these tools are used, relevant quote information — which can include a customer name and
        address, site address, survey notes, photo captions, and line-item details — is sent to our AI provider,
        <strong>OpenAI</strong>, to generate a draft. Drafts are always reviewed by a member of our team before
        being used. OpenAI processes this data on our behalf as a service provider.
    </p>

    <h2>The customer documents portal</h2>
    <p>
        SiteDesk includes a project document library that lets us share documents with customers and contractors
        under role-based permissions. Access is controlled so that customers can see only the projects belonging
        to them, and contractors can see only the projects they are assigned to. Document and folder activity is
        logged for security and audit purposes.
    </p>

    <h2>Sharing your information</h2>
    <p>
        We do not sell your personal information. We may share it with trusted service providers who help us run
        the platform, which may include our hosting provider, email delivery provider, and the AI provider
        described above. We may also disclose information where required by law or to establish, exercise, or
        defend legal claims.
    </p>

    <h2>Where your data is processed</h2>
    <p>
        Some of our service providers, including our AI provider, may process data outside the UK. Where data is
        transferred internationally, we take steps to ensure it remains protected in line with UK data protection law.
    </p>

    <h2>Data retention</h2>
    <p>
        We keep personal information for as long as needed to provide our services and maintain our business,
        customer, and contractor records, and to meet legal and accounting obligations (for example, retaining
        invoice and quote records for the period required by law). When information is no longer needed, we take
        steps to delete or anonymise it.
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
