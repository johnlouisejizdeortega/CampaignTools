<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Icons & install/home-screen assets (all generated from the same logo) --}}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    {{-- Social / link-preview cards --}}
    <meta property="og:title" content="Google Ads Dashboard">
    <meta property="og:description" content="Account analysis, benchmarks and source-cited optimization tips for your Google Ads account.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Google Ads Dashboard">
    <meta name="twitter:description" content="Account analysis, benchmarks and source-cited optimization tips for your Google Ads account.">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">

    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
