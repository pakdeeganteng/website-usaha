<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f7fa">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel Admin') | {{ config('app.name', 'DapurHarian') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-auth-page">
    @yield('content')
</body>
</html>
