<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Orders extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ASSEMBLED = 'assembled';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_POSTED = 'posted';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_PROCESSING => 'В обработке',
        self::STATUS_ASSEMBLED => 'Собран',
        self::STATUS_SHIPPED => 'Отправлено',
        self::STATUS_POSTED => 'Создан на почте',
        self::STATUS_DELIVERED => 'Доставлен',
        self::STATUS_CANCELLED => 'Отменён',
    ];

    public const STATUSES_WITH_TRACKING = [
        self::STATUS_SHIPPED,
        self::STATUS_POSTED,
    ];

    protected $fillable = [
        'delivery_service',
        'city',
        'warehouse',
        'manual_address',
        'name',
        'lastname',
        'fathername',
        'phone',
        'email',
        'comment',
        'cart',
        'total_price',
        'payment',
        'status',
        'tracking_number',
    ];

    protected $encryptable = [
        'delivery_service',
        'city',
        'warehouse',
        'manual_address',
        'name',
        'lastname',
        'fathername',
        'phone',
        'email',
        'comment',
        'cart',
        'total_price',
        'payment',
    ];

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable, true) && $value !== null) {
            $plain = (string) $value;
            // Не шифруем повторно уже зашифрованный payload.
            if (! self::looksLikeEncryptedPayload($plain)) {
                $value = Crypt::encryptString($plain);
            }
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable, true) && ! is_null($value) && $value !== '') {
            return self::decryptValue((string) $value);
        }

        return $value;
    }

    public static function looksLikeEncryptedPayload(string $value): bool
    {
        if ($value === '' || (! str_starts_with($value, 'eyJ') && ! str_starts_with($value, 'eyJp'))) {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && isset($json['iv'], $json['value'], $json['mac'])
            && is_string($json['iv'])
            && is_string($json['value'])
            && is_string($json['mac']);
    }

    /**
     * Расшифровывает до plaintext (несколько слоёв) либо возвращает исходную строку.
     */
    public static function decryptValue(string $value): string
    {
        $current = $value;

        for ($i = 0; $i < 5; $i++) {
            if (! self::looksLikeEncryptedPayload($current)) {
                return $current;
            }

            try {
                $current = Crypt::decryptString($current);
            } catch (Throwable) {
                // Старый APP_KEY / битый payload — отдаём как есть, без фатала.
                return $current;
            }
        }

        return $current;
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        $totalPrice = $this->total_price;
        if (is_numeric($totalPrice)) {
            return number_format((float) $totalPrice, 2, ',', ' ').' ₴';
        }

        return '0,00 ₴';
    }

    public function getNumericTotalPriceAttribute(): float
    {
        $totalPrice = $this->total_price;

        return is_numeric($totalPrice) ? (float) $totalPrice : 0;
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->name ?? '').' '.($this->lastname ?? ''));
        if ($name === '' || self::looksLikeEncryptedPayload($name)) {
            return '—';
        }

        return $name;
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d.m.Y H:i') : 'Не указано';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status ?? self::STATUS_NEW] ?? (string) $this->status;
    }

    public function requiresTrackingNotification(): bool
    {
        return in_array($this->status, self::STATUSES_WITH_TRACKING, true);
    }

    /**
     * Безопасное чтение корзины (без исключений наружу).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCartItemsAttribute(): array
    {
        try {
            $cart = $this->attributes['cart'] ?? null;
            if ($cart === null || $cart === '') {
                return [];
            }

            // Берём raw + ручная расшифровка, чтобы не зависеть от цепочки accessor.
            $plain = is_string($cart) ? self::decryptValue($cart) : $cart;

            if (is_array($plain)) {
                return array_values($plain);
            }

            if (! is_string($plain) || $plain === '') {
                return [];
            }

            $decoded = json_decode($plain, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }

            // Иногда plaintext — снова encrypted payload / сериализованный JSON.
            if (self::looksLikeEncryptedPayload($plain)) {
                try {
                    $again = Crypt::decryptString($plain);
                    $decoded = json_decode($again, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return array_values($decoded);
                    }
                } catch (Throwable) {
                    // ignore
                }
            }

            return [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Человекочитаемое значение для админки (не показывает ciphertext).
     */
    public function displayValue(string $key, string $fallback = '—'): string
    {
        $value = $this->{$key};
        if ($value === null || $value === '') {
            return $fallback;
        }

        $string = is_scalar($value) ? trim((string) $value) : $fallback;
        if ($string === '' || self::looksLikeEncryptedPayload($string)) {
            return $fallback;
        }

        return $string;
    }
}
