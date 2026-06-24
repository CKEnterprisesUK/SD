<?php

namespace App\Mail;

use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractorInvoiceReturned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContractorInvoice $invoice,
        public PortalSetting $settings,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice returned for changes: ' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contractor-invoice-returned',
            with: [
                'invoice' => $this->invoice,
                'settings' => $this->settings,
                'editUrl' => route('contractor.invoices.edit', $this->invoice),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}