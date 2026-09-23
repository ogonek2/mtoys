{{-- Shared customer email chrome. Sections: $slot (main), optional $preheader --}}
@php
    $storeName = $shop['store_name'] ?? 'Mtoys';
    $siteUrl = rtrim($siteUrl ?? config('app.url', 'https://mtoys.com.ua'), '/');
    $phone = $shop['contact_phone'] ?? '063 63 100 41';
    $email = $shop['contact_email'] ?? 'office@mtoys.com.ua';
    $address = $shop['contact_address'] ?? 'вул. Фабрична, номер контейнер 2177, 7 км, Одеса';
    $phoneHref = 'tel:'.preg_replace('/\D+/', '', $phone);
@endphp
<!DOCTYPE html>
<html lang="uk" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? $storeName }}</title>
    <!--[if mso]>
    <style type="text/css">
        table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#E8F0F4;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    @if(!empty($preheader))
        <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
            {{ $preheader }}
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#E8F0F4;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(14,58,69,0.12);">
                    {{-- Accent top bar --}}
                    <tr>
                        <td style="height:6px;line-height:6px;font-size:0;background:linear-gradient(90deg,#FF6B4A 0%,#FFB347 50%,#3DDC97 100%);background-color:#FF6B4A;">&nbsp;</td>
                    </tr>

                    {{-- Brand header --}}
                    <tr>
                        <td style="background-color:#0E3A45;padding:28px 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $siteUrl }}" style="text-decoration:none;">
                                            <span style="font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:800;letter-spacing:0.5px;color:#ffffff;">
                                                M<span style="color:#FF6B4A;">toys</span>
                                            </span>
                                        </a>
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:rgba(255,255,255,0.65);margin-top:6px;letter-spacing:0.3px;">
                                            Інтернет-магазин іграшок · mtoys.com.ua
                                        </div>
                                    </td>
                                    <td align="right" valign="middle" style="padding-left:12px;">
                                        <a href="{{ $siteUrl }}" style="display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:#0E3A45;background-color:#FF6B4A;text-decoration:none;padding:10px 16px;border-radius:999px;">
                                            На сайт →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:0;background-color:#ffffff;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Contacts --}}
                    <tr>
                        <td style="background-color:#F2F7FA;padding:28px 32px;border-top:1px solid #D6E4EA;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;color:#0E3A45;padding-bottom:14px;">
                                        Контакти {{ $storeName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#3A5560;">
                                        <a href="{{ $phoneHref }}" style="color:#0E3A45;text-decoration:none;font-weight:700;">{{ $phone }}</a><br>
                                        <a href="mailto:{{ $email }}" style="color:#FF6B4A;text-decoration:none;font-weight:600;">{{ $email }}</a><br>
                                        <span style="color:#5A7380;">{{ $address }}</span><br>
                                        <a href="{{ $siteUrl }}" style="color:#0E3A45;text-decoration:underline;font-weight:600;">mtoys.com.ua</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#0E3A45;padding:20px 32px;text-align:center;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:rgba(255,255,255,0.55);">
                                © {{ date('Y') }} {{ $storeName }}. Усі права захищено.<br>
                                Цей лист надіслано автоматично з <a href="{{ $siteUrl }}" style="color:#FF6B4A;text-decoration:none;">mtoys.com.ua</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
