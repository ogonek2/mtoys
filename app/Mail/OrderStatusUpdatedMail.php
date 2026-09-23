<?php

namespace App\Mail;

use App\Models\Orders;
use App\Services\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Orders $order,
        public string $statusLabel,
    ) {}

    public function envelope(): Envelope
    {
        $store = ShopSettings::get('store_name', 'Mtoys');

        return new Envelope(
            subject: "Оновлення замовлення №{$this->order->id} — {$this->statusLabel} | {$store}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-updated',
            with: [
                'order' => $this->order,
                'statusLabel' => $this->statusLabel,
                'shop' => ShopSettings::public(),
                'requiresTracking' => $this->order->requiresTrackingNotification(),
            ],
        );
    }
}
