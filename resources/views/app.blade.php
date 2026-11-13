<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @if (request()->routeIs('welcome'))
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="Montre Nova">
            <meta property="og:title" content="Montre Nova | Curated Timepieces">
            <meta property="og:description" content="Explore our collection of brand-new and pre-owned watches.">
            <meta property="og:url" content="{{ route('welcome') }}">
            <meta property="og:image" content="{{ secure_asset('images/montre-nova-share.jpg') }}">
            <meta property="og:image:type" content="image/jpeg">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:image:alt" content="Blue-dial wristwatch displayed in a tan watch case">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
