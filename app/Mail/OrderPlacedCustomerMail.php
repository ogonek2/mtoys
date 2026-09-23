<?php

namespace App\Mail;

use App\Models\Orders;
use App\Services\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public Orders $order,
        public array $items,
        public float $total,
    ) {}

    public function envelope(): Envelope
    {
        $store = ShopSettings::get('store_name', 'Mtoys');

        return new Envelope(
            subject: "Замовлення №{$this->order->id} прийнято — {$store}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-placed-customer',
            with: [
                'order' => $this->order,
                'items' => $this->items,
                'total' => $this->total,
                'shop' => ShopSettings::public(),
            ],
        );
    }
}
