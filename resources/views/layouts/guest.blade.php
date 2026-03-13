<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'User Manual')</title>

        <link rel="icon" href="/logo-sximo.png" sizes="any">
        <link rel="icon" href="/logo-sximo.png" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/logo-sximo.png">

        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            html, body { height: 100%; }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">@yield('headerTitle', "User's Manual – WFH Attendance")</p>
                    <p class="truncate text-xs text-slate-500">Guest view</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Home</a>
                    <a href="{{ route('login') }}" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Login</a>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        @yield('scripts')
    </body>
</html>

