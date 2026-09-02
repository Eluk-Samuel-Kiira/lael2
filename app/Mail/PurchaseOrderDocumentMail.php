<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderDocumentMail extends Mailable
{
    public function __construct(
        public PurchaseOrder $order,
        public string $emailSubject,
        public ?string $customMessage = null,
    ) {}

    public function build()
    {
        $pdf = Pdf::loadView('procurement.purchase-order.pdf', ['order' => $this->order]);

        return $this->subject($this->emailSubject)
            ->view('emails.purchase-order-document', ['order' => $this->order, 'customMessage' => $this->customMessage])
            ->attachData($pdf->output(), $this->order->po_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}