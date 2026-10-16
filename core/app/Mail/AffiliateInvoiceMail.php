<?php

namespace App\Mail;

use App\Models\AffiliateOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public AffiliateOrder $order;

    public function __construct(AffiliateOrder $order)
    {
        $this->order = $order->loadMissing('items', 'state');
    }

    public function build()
    {
        $pdf = Pdf::loadView('Template::shop.pdf.invoice', ['order' => $this->order]);

        return $this->subject('Your Order Invoice & Redemption Code — ' . $this->order->order_code)
            ->view('Template::emails.affiliate_invoice', ['order' => $this->order])
            ->attachData($pdf->output(), 'invoice-' . $this->order->order_code . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
