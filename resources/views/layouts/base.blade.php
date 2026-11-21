<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d1d1b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accueil') · Cantine de Vatel</title>
    <link rel="icon" href="{{ asset('img/vatel_logo.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}?v=20">
    @stack('head')
</head>
<body class="no-demo">
    @include('partials.icons')
    <div id="app">@yield('body')</div>

    <div class="toast-wrap" id="toasts">
        @if (session('ok'))<div class="toast green">{{ session('ok') }}</div>@endif
        @if (session('info'))<div class="toast">{{ session('info') }}</div>@endif
        @if (session('error'))<div class="toast red">{{ session('error') }}</div>@endif
    </div>
    @stack('modals')
    <script src="{{ asset('vendor/qrcode.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}?v=5"></script>
    @stack('scripts')
</body>
</html>
