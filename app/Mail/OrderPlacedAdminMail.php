<?php

namespace App\Mail;

use App\Models\Orders;
use App\Services\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedAdminMail extends Mailable
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
        return new Envelope(
            subject: "Нове замовлення №{$this->order->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-placed-admin',
            with: [
                'order' => $this->order,
                'items' => $this->items,
                'total' => $this->total,
                'shop' => ShopSettings::public(),
            ],
        );
    }
}
