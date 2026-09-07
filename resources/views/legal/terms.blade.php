@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: 'CK Enterprises';
    $contactEmail = $portalSettings->accounts_email ?: 'support@ckenterprises.co.uk';
@endphp

<x-layouts.legal pageTitle="Terms of Service">
    <p>
        These Terms of Service ("Terms") govern your access to and use of SiteDesk, our online platform provided by
        {{ $companyName }} ("we", "us", "our"). As a customer, SiteDesk lets you view the quotes we prepare for you
        and access the documents relating to your projects. By accessing or using the platform, you agree to these Terms.
    </p>

    <h2>Accounts and access</h2>
    <p>
        Access to SiteDesk is by invitation. When you are given login access, you can view the projects, quotes, and
        documents that belong to you. You are responsible for keeping your login credentials secure, for all activity
        carried out under your account, and for notifying us promptly of any unauthorised use.
    </p>

    <h2>Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the platform for any unlawful or fraudulent purpose.</li>
        <li>Access, or attempt to access, data, projects, or accounts you are not authorised to view.</li>
        <li>Attempt to gain unauthorised access to the platform or its underlying systems.</li>
        <li>Interfere with or disrupt the integrity or performance of the platform.</li>
        <li>Upload content that is unlawful or that infringes the rights of others.</li>
    </ul>

    <h2>Quotes and estimates</h2>
    <p>
        Quotes and quote packs made available to you through SiteDesk are indicative and are subject to our formal
        written quotation and any agreed contract for the work. The platform is provided for your convenience in
        viewing and managing this information and does not itself form a contract for works.
    </p>

    <h2>Your content</h2>
    <p>
        You retain ownership of the data, documents, and photographs you upload. You grant us the rights necessary
        to host, process, and display that content for the purpose of providing the platform and delivering our
        services. You are responsible for ensuring you have the right to upload any content you provide.
    </p>

    <h2>Availability</h2>
    <p>
        We aim to keep SiteDesk available and reliable, but we do not guarantee uninterrupted access. We may
        suspend access for maintenance, updates, or operational reasons.
    </p>

    <h2>Limitation of liability</h2>
    <p>
        To the extent permitted by law, {{ $companyName }} is not liable for any indirect, incidental, or
        consequential loss arising from your use of the platform. Nothing in these Terms limits liability that
        cannot be limited or excluded under applicable law.
    </p>

    <h2>Suspension and termination</h2>
    <p>
        We may suspend or withdraw access if these Terms are breached or where necessary to protect the platform,
        our business, or other users. You may stop using the platform at any time.
    </p>

    <h2>Changes to these Terms</h2>
    <p>
        We may revise these Terms from time to time. Continued use of the platform after changes take effect
        constitutes acceptance of the updated Terms.
    </p>

    <h2>Governing law</h2>
    <p>
        These Terms are governed by the laws of England and Wales, and the courts of England and Wales have
        exclusive jurisdiction over any dispute arising from them.
    </p>

    <h2>Contact</h2>
    <p>
        Questions about these Terms can be sent to <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
    </p>
</x-layouts.legal>
