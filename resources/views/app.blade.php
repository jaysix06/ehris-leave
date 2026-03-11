<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to apply saved appearance before app bootstraps --}}
        <script>
            (function() {
                const cookieAppearance = '{{ $appearance ?? "light" }}';
                const storedAppearance = localStorage.getItem('appearance');
                const appearance = storedAppearance || cookieAppearance || 'light';
                document.documentElement.classList.toggle('dark', appearance === 'dark');
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="/logo-sximo.png" sizes="any">
        <link rel="icon" href="/logo-sximo.png" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/logo-sximo.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
