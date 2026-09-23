@php
    /** @var \App\Models\Orders|null $order */
    $order = $order ?? null;
    $items = $order ? ($order->cart_items ?? []) : [];
@endphp

<div style="overflow-x:auto;">
    @if (! $order)
        <p style="color:#6b7280;margin:0;">Нет данных заказа</p>
    @elseif (count($items) === 0)
        <p style="color:#6b7280;margin:0;">
            Не удалось прочитать состав корзины
            @if(\App\Models\Orders::looksLikeEncryptedPayload((string) ($order->getAttributes()['cart'] ?? '')))
                <br><span style="font-size:12px;">Данные заказа зашифрованы другим APP_KEY. На сервере в .env добавьте старый ключ:
                <code>APP_PREVIOUS_KEYS=base64:СТАРЫЙ_КЛЮЧ</code></span>
            @endif
        </p>
    @else
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f3f4f6;text-align:left;">
                    <th style="padding:8px;border:1px solid #e5e7eb;width:64px;">Фото</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;">Артикул</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;">Товар</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;text-align:center;">Кол-во</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;text-align:right;">Цена</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;text-align:right;">Сумма</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $qty = (int) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        $img = $item['image'] ?? $item['image_path'] ?? null;
                        if (is_string($img) && $img !== '' && ! str_starts_with($img, 'http') && ! str_starts_with($img, '//')) {
                            $img = asset('storage/'.ltrim($img, '/'));
                        }
                    @endphp
                    <tr>
                        <td style="padding:8px;border:1px solid #e5e7eb;vertical-align:middle;">
                            @if (is_string($img) && $img !== '')
                                <img src="{{ $img }}" alt="" width="56" height="56" style="object-fit:contain;background:#fafafa;display:block;" loading="lazy">
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="padding:8px;border:1px solid #e5e7eb;vertical-align:middle;">{{ $item['articule'] ?? '—' }}</td>
                        <td style="padding:8px;border:1px solid #e5e7eb;vertical-align:middle;">{{ $item['name'] ?? 'Товар' }}</td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;font-weight:700;font-size:16px;vertical-align:middle;">{{ $qty }}</td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right;vertical-align:middle;">{{ number_format($price, 0, '.', ' ') }} ₴</td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right;font-weight:700;vertical-align:middle;">{{ number_format($qty * $price, 0, '.', ' ') }} ₴</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
