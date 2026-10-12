<!DOCTYPE html>
<html lang="id" data-theme="light" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk') · {{ config('app.name') }}</title>

    <script>
        (function () {
            var KEY = 'bookflow.theme';
            var preference = 'system';

            try {
                var stored = window.localStorage.getItem(KEY);
                if (stored === 'light' || stored === 'dark' || stored === 'system') {
                    preference = stored;
                }
            } catch (error) {
                preference = 'system';
            }

            var dark = preference === 'dark'
                || (preference === 'system'
                    && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            document.documentElement.dataset.themePreference = preference;
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-canvas text-ink antialiased">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="mb-5 flex items-center justify-center gap-2.5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-control bg-brand-600">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-10 object-cover">
                </span>
                <div>
                    <p class="text-sm font-semibold text-ink">{{ config('app.name') }}</p>
                    <p class="text-[11px] text-ink-muted">Kelola Lebih Mudah, Pantau Lebih Akurat.</p>
                </div>
            </div>

            @yield('content')
        </div>
    </div>

    <div class="fixed top-4 right-4">
        <x-theme-switcher />
    </div>
</body>
</html>
