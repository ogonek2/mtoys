@php
    $siteUrl = rtrim($siteUrl ?? config('app.url', 'https://mtoys.com.ua'), '/');
    $tracking = $requiresTracking && !empty($order->tracking_number);
@endphp

@component('emails.layouts.customer', [
    'title' => 'Статус замовлення №'.$order->id,
    'preheader' => 'Замовлення №'.$order->id.': '.$statusLabel.($tracking ? '. ТТН: '.$order->tracking_number : ''),
    'shop' => $shop,
    'siteUrl' => $siteUrl,
])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:36px 32px 12px;text-align:center;">
                <div style="display:inline-block;background-color:#E8F8F1;color:#1A9B6C;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:8px 14px;border-radius:999px;margin-bottom:16px;">
                    Оновлення статусу
                </div>
                <h1 style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:1.3;font-weight:800;color:#0E3A45;">
                    Замовлення №{{ $order->id }}
                </h1>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#5A7380;">
                    Статус вашого замовлення змінено
                </p>
            </td>
        </tr>
    </table>

    {{-- Status card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:16px 32px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(135deg,#0E3A45 0%,#164E5C 100%);background-color:#0E3A45;border-radius:18px;">
                    <tr>
                        <td style="padding:28px 24px;text-align:center;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,0.55);margin-bottom:10px;">
                                Новий статус
                            </div>
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:#ffffff;line-height:1.3;">
                                {{ $statusLabel }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($tracking)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding:16px 32px 8px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFF0EC;border:2px solid #FF6B4A;border-radius:16px;">
                        <tr>
                            <td style="padding:20px 22px;">
                                <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:800;letter-spacing:0.8px;text-transform:uppercase;color:#FF6B4A;margin-bottom:8px;">
                                    Номер накладної (ТТН)
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:#0E3A45;letter-spacing:0.5px;">
                                    {{ $order->tracking_number }}
                                </div>
                                <p style="margin:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#5A7380;">
                                    Відстежте відправлення за цим номером на сайті служби доставки.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding:20px 32px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F2F7FA;border-radius:14px;">
                    <tr>
                        <td style="padding:18px 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#3A5560;">
                            Якщо у вас виникли питання щодо замовлення — напишіть нам на
                            <a href="mailto:{{ $shop['contact_email'] ?? 'office@mtoys.com.ua' }}" style="color:#FF6B4A;text-decoration:none;font-weight:700;">{{ $shop['contact_email'] ?? 'office@mtoys.com.ua' }}</a>
                            або зателефонуйте
                            <a href="tel:{{ preg_replace('/\D+/', '', $shop['contact_phone'] ?? '0636310041') }}" style="color:#0E3A45;text-decoration:none;font-weight:700;">{{ $shop['contact_phone'] ?? '063 63 100 41' }}</a>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:24px 32px 36px;">
                <a href="{{ $siteUrl }}" style="display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#ffffff;background-color:#FF6B4A;text-decoration:none;padding:14px 28px;border-radius:999px;">
                    Відкрити mtoys.com.ua
                </a>
            </td>
        </tr>
    </table>
@endcomponent
