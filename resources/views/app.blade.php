<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>Tenisline</title>
    <meta name="description" content="Tenisline: tenis de las mejores marcas en Zacapa, Chiquimula y Esquipulas. No box, sí precio.">
    <meta name="theme-color" content="#0a0a0a">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|archivo-black:400&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">

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
