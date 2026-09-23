<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    public static function send(string $text): bool
    {
        $botToken = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');

        // Fallback на env, если config ещё не закеширован / старый кэш без telegram.
        if ($botToken === '') {
            $botToken = (string) env('TG_BOT_TOKEN', '');
        }
        if ($chatId === '') {
            $chatId = (string) env('TG_CHAT_ID', '');
        }

        if ($botToken === '' || $chatId === '') {
            Log::warning('Telegram credentials missing (TG_BOT_TOKEN / TG_CHAT_ID).');

            return false;
        }

        $chunks = self::chunkMessage($text);
        $allOk = true;

        foreach ($chunks as $index => $chunk) {
            if (! self::sendRaw($botToken, $chatId, $chunk)) {
                $allOk = false;
                Log::error('Telegram chunk failed', [
                    'chunk' => $index + 1,
                    'chunks' => count($chunks),
                ]);
            }
        }

        return $allOk;
    }

    private static function sendRaw(string $botToken, string $chatId, string $text): bool
    {
        try {
            $http = Http::timeout(15)->asForm();
            if (! config('services.telegram.verify_ssl', false)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);

            $body = $response->json();
            if (! $response->ok() || ! data_get($body, 'ok')) {
                // Повтор без SSL на случай, если verify_ssl=true и сертификат ломается.
                if (config('services.telegram.verify_ssl', false)) {
                    $retry = Http::timeout(15)
                        ->withoutVerifying()
                        ->asForm()
                        ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $text,
                            'disable_web_page_preview' => true,
                        ]);
                    $retryBody = $retry->json();
                    if ($retry->ok() && data_get($retryBody, 'ok')) {
                        return true;
                    }
                    throw new \RuntimeException((string) data_get($retryBody, 'description', 'Telegram API error'));
                }

                throw new \RuntimeException((string) data_get($body, 'description', 'Telegram API error'));
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Telegram send failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Telegram limit = 4096 chars.
     *
     * @return array<int, string>
     */
    private static function chunkMessage(string $text): array
    {
        $limit = 4000;
        if (mb_strlen($text) <= $limit) {
            return [$text];
        }

        $chunks = [];
        $remaining = $text;
        while (mb_strlen($remaining) > $limit) {
            $slice = mb_substr($remaining, 0, $limit);
            $break = mb_strrpos($slice, "\n");
            if ($break === false || $break < (int) ($limit * 0.5)) {
                $break = $limit;
            }
            $chunks[] = mb_substr($remaining, 0, $break);
            $remaining = ltrim(mb_substr($remaining, $break));
        }
        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function sendContactRequest(array $data): bool
    {
        $lines = [
            '📩 Нове звернення з сайту',
            '📞 Телефон: '.($data['phone'] ?? '—'),
            '💬 Повідомлення: '.(trim((string) ($data['message'] ?? '')) !== '' ? $data['message'] : 'не вказано'),
        ];

        return self::send(implode("\n", $lines));
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function sendNewOrder(array $order, array $items, float $total): bool
    {
        $delivery = (string) ($order['delivery_service'] ?? '—');
        $deliveryLabel = match ($delivery) {
            'novaposhta' => 'Нова Пошта',
            'courier' => 'Курʼєр',
            'pickup' => 'Самовивіз',
            default => $delivery,
        };

        $payment = (string) ($order['payment'] ?? '—');
        $paymentLabel = match ($payment) {
            'cash' => 'Накладений платіж',
            'bank_transfer' => 'Банківський переказ',
            'card_payment' => 'Картка при отриманні',
            'pickup_payment' => 'Оплата при самовивозі',
            default => $payment,
        };

        $lines = [
            '🛒 НОВЕ ЗАМОВЛЕННЯ #'.($order['id'] ?? ''),
            '👤 '.trim(($order['lastname'] ?? '').' '.($order['name'] ?? '').' '.($order['fathername'] ?? '')),
            '📞 '.($order['phone'] ?? '—'),
            '✉️ '.($order['email'] ?? '—'),
            '🚚 '.$deliveryLabel,
        ];

        if (! empty($order['city'])) {
            $lines[] = '🏙 Місто: '.$order['city'];
        }
        if (! empty($order['warehouse'])) {
            $lines[] = '📦 Відділення: '.$order['warehouse'];
        }
        if (! empty($order['manual_address'])) {
            $lines[] = '📍 Адреса: '.$order['manual_address'];
        }

        $lines[] = '💳 '.$paymentLabel;
        $lines[] = '💰 '.number_format($total, 0, '.', ' ').' ₴';

        if (! empty($order['comment'])) {
            $lines[] = '🗒 Коментар: '.$order['comment'];
        }

        $lines[] = '';
        $lines[] = 'Товари:';

        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $line = sprintf(
                '• %s × %s — %s ₴',
                $item['name'] ?? 'Товар',
                $qty,
                number_format($price * $qty, 0, '.', ' ')
            );
            if (! empty($item['articule'])) {
                $line .= ' ['.$item['articule'].']';
            }
            $lines[] = $line;
        }

        $ok = self::send(implode("\n", $lines));
        if (! $ok) {
            Log::error('Order telegram notification failed', [
                'order_id' => $order['id'] ?? null,
            ]);
        }

        return $ok;
    }
}
