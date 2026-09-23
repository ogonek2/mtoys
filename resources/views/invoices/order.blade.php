<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Інвойс замовлення №{{ $orderData['id'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-details, .customer-details {
            width: 45%;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-left: 4px solid #333;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .total-final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Заголовок -->
    <div class="header">
        <div class="company-name">ZMART SHOP</div>
        <div>Інтернет-магазин техніки</div>
        <div>Контакти: support@zmart.com.ua</div>
    </div>
    
    <div class="invoice-title">ІНВОЙС №{{ $orderData['id'] }}</div>
    
    <!-- Информация о заказе и клиенте -->
    <div class="invoice-info">
        <div class="invoice-details">
            <div class="section-title">Інформація про замовлення</div>
            <div class="info-row">
                <span class="info-label">Номер замовлення:</span>
                {{ $orderData['id'] }}
            </div>
            <div class="info-row">
                <span class="info-label">Дата замовлення:</span>
                {{ $orderData['created_at']->format('d.m.Y H:i') }}
            </div>
            <div class="info-row">
                <span class="info-label">Спосіб доставки:</span>
                @switch($orderData['delivery_service'])
                    @case('novaposhta')
                        Нова Пошта
                        @break
                    @case('ukrposhta')
                        Укрпошта
                        @break
                    @case('meest')
                        Meest Express
                        @break
                    @case('pickup')
                        Самовивіз
                        @break
                    @default
                        {{ $orderData['delivery_service'] }}
                @endswitch
            </div>
            <div class="info-row">
                <span class="info-label">Спосіб оплати:</span>
                @switch($orderData['payment'])
                    @case('card')
                        Банківська карта
                        @break
                    @case('cash')
                        Готівка
                        @break
                    @case('nalogenniy')
                        Накладений платіж
                        @break
                    @case('bank_transfer')
                        Банківський переказ
                        @break
                    @default
                        {{ $orderData['payment'] }}
                @endswitch
            </div>
        </div>
        
        <div class="customer-details">
            <div class="section-title">Дані клієнта</div>
            <div class="info-row">
                <span class="info-label">ПІБ:</span>
                {{ trim($orderData['name'] . ' ' . $orderData['lastname'] . ' ' . ($orderData['fathername'] ?? '')) }}
            </div>
            <div class="info-row">
                <span class="info-label">Телефон:</span>
                {{ $orderData['phone'] }}
            </div>
            @if($orderData['city'])
            <div class="info-row">
                <span class="info-label">Місто:</span>
                {{ $orderData['city'] }}
            </div>
            @endif
            @if($orderData['warehouse'])
            <div class="info-row">
                <span class="info-label">Відділення:</span>
                {{ $orderData['warehouse'] }}
            </div>
            @endif
            @if($orderData['manual_address'])
            <div class="info-row">
                <span class="info-label">Адреса:</span>
                {{ $orderData['manual_address'] }}
            </div>
            @endif
        </div>
    </div>
    
    <!-- Товары -->
    <div class="section-title">Товари в замовленні</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 50%;">Назва товару</th>
                <th style="width: 15%;" class="text-center">Кількість</th>
                <th style="width: 15%;" class="text-right">Ціна за од.</th>
                <th style="width: 15%;" class="text-right">Сума</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSum = 0;
                $itemNumber = 1;
            @endphp
            @foreach($orderData['cart_items'] as $item)
                @php
                    $quantity = $item['quantity'] ?? 1;
                    $price = $item['price'] ?? 0;
                    $itemTotal = $price * $quantity;
                    $totalSum += $itemTotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $itemNumber++ }}</td>
                    <td>
                        {{ $item['name'] ?? 'Невідомий товар' }}
                        @if(isset($item['articule']))
                            <br><small>Артикул: {{ $item['articule'] }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $quantity }} шт.</td>
                    <td class="text-right">{{ number_format($price, 2) }} ₴</td>
                    <td class="text-right">{{ number_format($itemTotal, 2) }} ₴</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold;">Всього до сплати:</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($totalSum, 2) }} ₴</td>
            </tr>
        </tfoot>
    </table>
    
    @if($orderData['comment'])
    <div class="section-title">Коментар до замовлення</div>
    <div style="background-color: #f9f9f9; padding: 10px; border-left: 4px solid #333; margin-bottom: 20px;">
        {{ $orderData['comment'] }}
    </div>
    @endif
    
    <!-- Подвал -->
    <div class="footer">
        <p>Дякуємо за ваше замовлення!</p>
        <p>З повагою, команда ZMART SHOP</p>
        <p>Дата створення інвойсу: {{ date('d.m.Y H:i') }}</p>
    </div>
</body>
</html>
