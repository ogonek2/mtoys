<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Замовлення №{{ $order->id }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1A2B36; line-height: 1.5; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px; margin: 0 0 8px;">Дякуємо за замовлення!</h1>
    <p style="margin: 0 0 16px; color: #555;">Замовлення <strong>№{{ $order->id }}</strong> успішно прийнято.</p>

    <h2 style="font-size: 16px; margin: 24px 0 8px;">Ваші дані</h2>
    <p style="margin: 0;">
        {{ trim(($order->lastname ?? '').' '.($order->name ?? '').' '.($order->fathername ?? '')) }}<br>
        Телефон: {{ $order->phone }}<br>
        Email: {{ $order->email }}<br>
        Доставка: {{ $order->delivery_service }}<br>
        @if($order->city) Місто: {{ $order->city }}<br>@endif
        @if($order->warehouse) Відділення: {{ $order->warehouse }}<br>@endif
        @if($order->manual_address) Адреса: {{ $order->manual_address }}<br>@endif
        Оплата: {{ $order->payment }}
        @if($order->comment)<br>Коментар: {{ $order->comment }}@endif
    </p>

    <h2 style="font-size: 16px; margin: 24px 0 8px;">Склад замовлення</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; border-bottom: 1px solid #ddd; padding: 8px 4px;">Товар</th>
                <th style="text-align: center; border-bottom: 1px solid #ddd; padding: 8px 4px;">К-сть</th>
                <th style="text-align: right; border-bottom: 1px solid #ddd; padding: 8px 4px;">Сума</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td style="padding: 8px 4px; border-bottom: 1px solid #eee;">
                        {{ $item['name'] ?? 'Товар' }}
                        @if(!empty($item['articule']))
                            <div style="color:#888;font-size:12px;">Артикул: {{ $item['articule'] }}</div>
                        @endif
                    </td>
                    <td style="text-align: center; padding: 8px 4px; border-bottom: 1px solid #eee;">{{ $item['quantity'] ?? 1 }}</td>
                    <td style="text-align: right; padding: 8px 4px; border-bottom: 1px solid #eee;">
                        {{ number_format(((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 1)), 0, '.', ' ') }} ₴
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size: 18px; font-weight: bold; margin: 16px 0;">
        Разом: {{ number_format($total, 0, '.', ' ') }} ₴
    </p>

    <h2 style="font-size: 16px; margin: 24px 0 8px;">Контакти магазину</h2>
    <p style="margin: 0; color: #555;">
        {{ $shop['store_name'] ?? 'Mtoys' }}<br>
        @if(!empty($shop['contact_phone']))Тел: {{ $shop['contact_phone'] }}<br>@endif
        @if(!empty($shop['contact_email']))Email: {{ $shop['contact_email'] }}<br>@endif
        @if(!empty($shop['contact_address'])){{ $shop['contact_address'] }}@endif
    </p>
</body>
</html>
