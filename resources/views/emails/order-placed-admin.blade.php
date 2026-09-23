<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Нове замовлення №{{ $order->id }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1A2B36; line-height: 1.5; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">Нове замовлення №{{ $order->id }}</h1>
    <p>
        {{ trim(($order->lastname ?? '').' '.($order->name ?? '')) }}<br>
        {{ $order->phone }} / {{ $order->email }}<br>
        {{ $order->delivery_service }} / {{ $order->payment }}<br>
        Сума: <strong>{{ number_format($total, 0, '.', ' ') }} ₴</strong>
    </p>
    <ul>
        @foreach($items as $item)
            <li>{{ $item['name'] ?? 'Товар' }} × {{ $item['quantity'] ?? 1 }} — {{ number_format((float)($item['price'] ?? 0), 0, '.', ' ') }} ₴</li>
        @endforeach
    </ul>
</body>
</html>
