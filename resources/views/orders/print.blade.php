<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення №{{ $order->id }} — збір</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 16px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
        .box { border: 1px solid #ccc; padding: 10px 12px; }
        .box h2 { font-size: 13px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: .04em; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 8px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; font-size: 12px; }
        .img { width: 56px; height: 56px; object-fit: contain; background: #fafafa; border: 1px solid #eee; }
        .check { width: 28px; text-align: center; }
        .qty { font-size: 18px; font-weight: 700; text-align: center; }
        .total { margin-top: 14px; font-size: 16px; font-weight: 700; text-align: right; }
        .actions { margin-bottom: 14px; }
        .actions button { padding: 8px 14px; font-size: 14px; cursor: pointer; }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Друкувати</button>
    </div>

    <h1>Замовлення №{{ $order->id }}</h1>
    <div class="meta">
        {{ $order->formatted_created_at }} · Статус: {{ $order->status_label }}
        @if($order->tracking_number) · Накладна: {{ $order->tracking_number }} @endif
    </div>

    <div class="grid">
        <div class="box">
            <h2>Покупець</h2>
            <div>{{ trim(($order->lastname ?? '').' '.($order->name ?? '').' '.($order->fathername ?? '')) }}</div>
            <div>Тел: {{ $order->phone }}</div>
            <div>Email: {{ $order->email }}</div>
            @if($order->comment)<div>Коментар: {{ $order->comment }}</div>@endif
        </div>
        <div class="box">
            <h2>Доставка / оплата</h2>
            <div>{{ $order->delivery_service }}</div>
            @if($order->city)<div>Місто: {{ $order->city }}</div>@endif
            @if($order->warehouse)<div>Відділення: {{ $order->warehouse }}</div>@endif
            @if($order->manual_address)<div>Адреса: {{ $order->manual_address }}</div>@endif
            <div>Оплата: {{ $order->payment }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="check">✓</th>
                <th>Фото</th>
                <th>Артикул</th>
                <th>Назва</th>
                <th>К-сть</th>
                <th>Ціна</th>
                <th>Сума</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $qty = (int) ($item['quantity'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                    $image = $item['image'] ?? $item['image_path'] ?? '';
                @endphp
                <tr>
                    <td class="check">☐</td>
                    <td>
                        @if($image)
                            <img class="img" src="{{ $image }}" alt="">
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $item['articule'] ?? '—' }}</td>
                    <td>{{ $item['name'] ?? 'Товар' }}</td>
                    <td class="qty">{{ $qty }}</td>
                    <td>{{ number_format($price, 0, '.', ' ') }} ₴</td>
                    <td>{{ number_format($price * $qty, 0, '.', ' ') }} ₴</td>
                </tr>
            @empty
                <tr><td colspan="7">Немає позицій</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">Разом: {{ $order->formatted_total_price }}</div>
    <p style="margin-top: 24px; color: #777; font-size: 12px;">{{ $shop['store_name'] ?? 'Mtoys' }} · лист для збору на складі</p>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
</body>
</html>
