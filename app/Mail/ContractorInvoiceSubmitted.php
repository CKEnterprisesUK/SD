<?php

namespace App\Mail;

use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractorInvoiceSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContractorInvoice $invoice,
        public PortalSetting $settings,
        public string $recipientType = 'accounts',
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contractor invoice submitted: ' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contractor-invoice-submitted',
            with: [
                'invoice' => $this->invoice,
                'settings' => $this->settings,
                'recipientType' => $this->recipientType,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.contractor-invoice', [
            'invoice' => $this->invoice,
            'contractor' => $this->invoice->contractor,
            'settings' => $this->settings,
        ])->setPaper('a4');

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $this->invoice->invoice_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}