<?php

namespace App\Mail\Sale;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SaleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sale;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($sale)
    {
        $this->sale = $sale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("ECOMMERCE DETALLADO DE COMPRA")->view('sale.sale_email');
    }
}
