<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderReceiptMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $emailSubject,
        public ?string $customMessage = null,
    ) {}

    public function build()
    {
        $pdf = Pdf::loadView('orders.receipt.pdf', ['order' => $this->order]);

        return $this->subject($this->emailSubject)
            ->view('emails.order-receipt', ['order' => $this->order, 'customMessage' => $this->customMessage])
            ->attachData($pdf->output(), $this->order->order_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}