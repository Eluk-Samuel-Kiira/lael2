<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{ Auth, DB, Log, Mail };

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string $customMessage;
    public string $emailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, string $subject = null, string $customMessage = null)
    {
        $this->invoice = $invoice;
        $this->emailSubject = $subject ?? __('payments.invoice_subject', [
            'number' => $invoice->invoice_number,
            'app_name' => getUIOptions('app_name', $tenantId),
        ]);
        $this->customMessage = $customMessage ?? __('payments.invoice_email_body', [
            'name' => $invoice->billing_name,
            'number' => $invoice->invoice_number,
            'total' => number_format($invoice->total, 2),
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject, // Use emailSubject here
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'customMessage' => $this->customMessage,
                'appName' => getUIOptions('app_name', Auth::user()->tenant_id),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Generate PDF
        $pdf = Pdf::loadView('orders.invoice.pdf', ['invoice' => $this->invoice]);
        $pdfContent = $pdf->output();

        return [
            Attachment::fromData(fn () => $pdfContent, $this->invoice->invoice_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}