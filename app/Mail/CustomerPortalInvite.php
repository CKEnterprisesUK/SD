<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\PortalSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Branded portal invitation email sent to a customer contact.
 *
 * Carries the password-setup link so the recipient can set their password and
 * sign in to the portal. Company branding (name, address, logo) is rendered by
 * the shared emails.layout via PortalSetting::current().
 */
class CustomerPortalInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $recipientName,
        public string $setupUrl,
        public PortalSetting $settings,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $portalName = $this->settings->portal_name ?: 'the portal';

        return new Envelope(
            subject: 'You have been invited to ' . $portalName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-portal-invite',
            with: [
                'customer' => $this->customer,
                'recipientName' => $this->recipientName,
                'setupUrl' => $this->setupUrl,
                'settings' => $this->settings,
            ],
        );
    }
}
