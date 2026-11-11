<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Masakan rumahan yang dibuat fresh dengan bahan pilihan. Pesan menu favoritmu di DapurHarian.')">
    <meta name="theme-color" content="#fbf7f0">
    <title>@yield('title', config('app.name', 'DapurHarian')) | {{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.navigation')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
