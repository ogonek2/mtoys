<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Статус замовлення №{{ $order->id }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1A2B36; line-height: 1.5; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">Оновлення статусу замовлення №{{ $order->id }}</h1>
    <p>Новий статус: <strong>{{ $statusLabel }}</strong></p>

    @if($requiresTracking && $order->tracking_number)
        <p style="background:#EEF8F7;padding:12px 16px;border-left:4px solid #FF6B4A;">
            Номер накладної: <strong>{{ $order->tracking_number }}</strong>
        </p>
        <p>Ви можете відстежити відправлення за цим номером у службі доставки.</p>
    @endif

    <p style="color:#555;margin-top:24px;">
        {{ $shop['store_name'] ?? 'Mtoys' }}<br>
        @if(!empty($shop['contact_phone'])){{ $shop['contact_phone'] }}<br>@endif
        @if(!empty($shop['contact_email'])){{ $shop['contact_email'] }}@endif
    </p>
</body>
</html>
