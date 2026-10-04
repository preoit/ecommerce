<!DOCTYPE html>
@php
    $websiteName = data_get($page, 'props.website.name') ?: config('app.name', 'Commerce');
    $websiteFavicon = data_get($page, 'props.website.favicon');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#167c5a">
        <meta name="website-name" content="{{ $websiteName }}">
        @if ($websiteFavicon)<link rel="icon" href="{{ $websiteFavicon }}">@endif

        <title inertia>{{ $websiteName }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite('resources/js/app.jsx')
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
