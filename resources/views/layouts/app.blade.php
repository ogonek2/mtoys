<!doctype html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">



<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#0E3A45">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">



    @yield('seo')



    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="shortcut icon" href="{{ asset('storage/src/favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        window.__SHOP_SETTINGS__ = @json(shop_settings_public());
    </script>

    @stack('styles')

</head>



<body class="bg-[#F2F7FA]">

    <div id="app" class="wrapper">

        @include('includes.main.nav')

        <div class="content-wrapper">
            @yield('content')
        </div>

        @include('includes.main.footer')



        <callback-modal data-action="{{ route('contact_request') }}"></callback-modal>

        <toast-notification></toast-notification>

    </div>



    @stack('scripts')

</body>



</html>


