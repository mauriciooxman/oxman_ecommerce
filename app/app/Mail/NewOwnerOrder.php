<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class NewOwnerOrder extends Mailable
{
    public function __construct(public string $reference, public string $paymentState, public string $panelUrl) {}

    public function build(): static
    {
        return $this->subject('Nuevo pedido '.$this->reference)->text('mail.new-owner-order');
    }
}
