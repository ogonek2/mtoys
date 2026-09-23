@php
    $deliveryLabels = [
        'novaposhta' => 'Нова Пошта',
        'courier' => 'Курʼєрська доставка',
        'pickup' => 'Самовивіз',
    ];
    $paymentLabels = [
        'cash' => 'Накладений платіж',
        'bank_transfer' => 'Банківський переказ',
        'card_payment' => 'Картка при отриманні',
        'pickup_payment' => 'Оплата при самовивозі',
    ];
    $deliveryKey = (string) ($order->delivery_service ?? '');
    $paymentKey = (string) ($order->payment ?? '');
    $deliveryLabel = $deliveryLabels[$deliveryKey] ?? ($deliveryKey !== '' ? $deliveryKey : '—');
    $paymentLabel = $paymentLabels[$paymentKey] ?? ($paymentKey !== '' ? $paymentKey : '—');
    $customerName = trim(($order->lastname ?? '').' '.($order->name ?? '').' '.($order->fathername ?? ''));
    $siteUrl = rtrim($siteUrl ?? config('app.url', 'https://mtoys.com.ua'), '/');
    $fmt = fn ($n) => number_format((float) $n, 0, '.', ' ');
@endphp

@component('emails.layouts.customer', [
    'title' => 'Замовлення №'.$order->id,
    'preheader' => 'Дякуємо! Замовлення №'.$order->id.' прийнято. Сума '.$fmt($total).' ₴',
    'shop' => $shop,
    'siteUrl' => $siteUrl,
])
    {{-- Hero --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:36px 32px 8px;text-align:center;">
                <div style="display:inline-block;background-color:#FFF0EC;color:#FF6B4A;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:8px 14px;border-radius:999px;margin-bottom:16px;">
                    Замовлення прийнято
                </div>
                <h1 style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:26px;line-height:1.25;font-weight:800;color:#0E3A45;">
                    Дякуємо за покупку!
                </h1>
                <p style="margin:0 auto;max-width:420px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#5A7380;">
                    Ми вже готуємо ваше замовлення <strong style="color:#0E3A45;">№{{ $order->id }}</strong>.
                    Незабаром надішлемо оновлення статусу.
                </p>
            </td>
        </tr>
    </table>

    {{-- Order meta pills --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:20px 32px 8px;">
        <tr>
            <td style="padding:0 32px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F2F7FA;border-radius:16px;">
                    <tr>
                        <td width="50%" style="padding:18px 20px;border-right:1px solid #D6E4EA;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:#7A93A0;margin-bottom:6px;">Номер</div>
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:800;color:#0E3A45;">№{{ $order->id }}</div>
                        </td>
                        <td width="50%" style="padding:18px 20px;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:#7A93A0;margin-bottom:6px;">Сума</div>
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:800;color:#FF6B4A;">{{ $fmt($total) }} ₴</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Customer + delivery --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:8px 32px 8px;">
                <h2 style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#0E3A45;">
                    Деталі доставки
                </h2>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #D6E4EA;border-radius:14px;overflow:hidden;">
                    @if($customerName !== '')
                        <tr>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;width:38%;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Отримувач</td>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $customerName }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Телефон</td>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $order->phone }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Email</td>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $order->email }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Доставка</td>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $deliveryLabel }}</td>
                    </tr>
                    @if($order->city)
                        <tr>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Місто</td>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $order->city }}</td>
                        </tr>
                    @endif
                    @if($order->warehouse)
                        <tr>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Відділення</td>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $order->warehouse }}</td>
                        </tr>
                    @endif
                    @if($order->manual_address)
                        <tr>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;border-bottom:1px solid #E8F0F4;">Адреса</td>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;border-bottom:1px solid #E8F0F4;">{{ $order->manual_address }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;@if($order->comment)border-bottom:1px solid #E8F0F4;@endif">Оплата</td>
                        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;@if($order->comment)border-bottom:1px solid #E8F0F4;@endif">{{ $paymentLabel }}</td>
                    </tr>
                    @if($order->comment)
                        <tr>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A93A0;background:#FAFCFD;">Коментар</td>
                            <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;">{{ $order->comment }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:24px 32px 8px;">
                <h2 style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#0E3A45;">
                    Склад замовлення
                </h2>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #D6E4EA;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:10px 14px;background-color:#0E3A45;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.75);">Товар</td>
                        <td align="center" style="padding:10px 10px;background-color:#0E3A45;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.75);width:64px;">К-сть</td>
                        <td align="right" style="padding:10px 14px;background-color:#0E3A45;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:rgba(255,255,255,0.75);width:90px;">Сума</td>
                    </tr>
                    @foreach($items as $index => $item)
                        @php
                            $qty = (int) ($item['quantity'] ?? 1);
                            $price = (float) ($item['price'] ?? 0);
                            $rowBg = $index % 2 === 0 ? '#ffffff' : '#F7FBFC';
                        @endphp
                        <tr>
                            <td style="padding:14px;background-color:{{ $rowBg }};border-top:1px solid #E8F0F4;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#0E3A45;vertical-align:top;">
                                {{ $item['name'] ?? 'Товар' }}
                                @if(!empty($item['articule']))
                                    <div style="margin-top:4px;font-size:12px;font-weight:400;color:#7A93A0;">Арт. {{ $item['articule'] }}</div>
                                @endif
                            </td>
                            <td align="center" style="padding:14px 8px;background-color:{{ $rowBg }};border-top:1px solid #E8F0F4;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#0E3A45;vertical-align:top;">
                                {{ $qty }}
                            </td>
                            <td align="right" style="padding:14px;background-color:{{ $rowBg }};border-top:1px solid #E8F0F4;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#0E3A45;vertical-align:top;">
                                {{ $fmt($price * $qty) }} ₴
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" style="padding:16px 14px;background-color:#FFF0EC;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#0E3A45;">
                            Разом до сплати
                        </td>
                        <td align="right" style="padding:16px 14px;background-color:#FFF0EC;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:800;color:#FF6B4A;">
                            {{ $fmt($total) }} ₴
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:28px 32px 36px;">
                <a href="{{ $siteUrl }}" style="display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#ffffff;background-color:#FF6B4A;text-decoration:none;padding:14px 28px;border-radius:999px;box-shadow:0 8px 20px rgba(255,107,74,0.35);">
                    Перейти на mtoys.com.ua
                </a>
                <p style="margin:16px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#7A93A0;">
                    Питання щодо замовлення? Напишіть на
                    <a href="mailto:{{ $shop['contact_email'] ?? 'office@mtoys.com.ua' }}" style="color:#FF6B4A;text-decoration:none;font-weight:700;">{{ $shop['contact_email'] ?? 'office@mtoys.com.ua' }}</a>
                    або зателефонуйте
                    <a href="tel:{{ preg_replace('/\D+/', '', $shop['contact_phone'] ?? '0636310041') }}" style="color:#0E3A45;text-decoration:none;font-weight:700;">{{ $shop['contact_phone'] ?? '063 63 100 41' }}</a>
                </p>
            </td>
        </tr>
    </table>
@endcomponent
