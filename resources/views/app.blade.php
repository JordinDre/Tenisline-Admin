<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($seo = $page['props']['seo'] ?? null)
    <title inertia>{{ $seo['title'] ?? 'Tenisline | No box, sí precio' }}</title>
    <meta name="description" content="{{ $seo['description'] ?? 'Tenisline: tenis de las mejores marcas en Zacapa, Chiquimula y Esquipulas. No box, sí precio.' }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="geo.region" content="GT">

    <!-- Open Graph / Twitter: vistas previas en WhatsApp, Facebook e Instagram -->
    <meta property="og:site_name" content="Tenisline">
    <meta property="og:locale" content="es_GT">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['title'] ?? 'Tenisline' }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:image" content="{{ $seo['image'] ?? url('/images/og-tenisline.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? 'Tenisline' }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    <meta name="twitter:image" content="{{ $seo['image'] ?? url('/images/og-tenisline.jpg') }}">
    @foreach (($seo['jsonld'] ?? []) as $dato)
        <script type="application/ld+json">{!! json_encode($dato, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="{{ parse_url(config('filesystems.disks.s3.url'), PHP_URL_HOST) ? '//'.parse_url(config('filesystems.disks.s3.url'), PHP_URL_HOST) : '//s3.amazonaws.com' }}">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|archivo-black:400&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ asset('favicon-192.png') }}" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('favicon-192.png') }}">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    

    <!-- Scripts -->
    @routes
    @inertiaHead
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="bg-white font-sans text-neutral-900 antialiased">
    @inertia
</body>

</html>
