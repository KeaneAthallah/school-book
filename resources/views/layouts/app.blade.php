<!DOCTYPE html>
<html lang="id" data-theme="light" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ARKARA PM (Aplikasi Rekapitulasi, Kendali, dan Analisis Rekening dan Administrasi – Prana Media) merupakan aplikasi berbasis digital yang dirancang untuk membantu CV. Prana Media meningkatkan efektivitas pengelolaan operasional dan keuangan usaha melalui pencatatan pemasukan dan pengeluaran, pengelolaan stok dan penjualan buku, penyusunan laporan keuangan, serta penyajian dashboard analisis keuangan secara terintegrasi.">
    <meta name="theme-color" content="#2560e8">
    <meta property="og:type" content="website">
    <meta property="og:title" content="ARKARA PM — Kelola Lebih Mudah, Pantau Lebih Akurat.">
    <meta property="og:description" content="Sistem manajemen operasional dan keuangan usaha CV. Prana Media: pencatatan pemasukan dan pengeluaran, pengelolaan stok dan penjualan buku, laporan keuangan, dan dashboard analisis keuangan terintegrasi.">
    <meta property="og:image" content="{{ asset('logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    {{--
        Resolve the theme before styles paint so a saved preference never
        flashes the wrong palette. Keep this in sync with the Alpine
        `themeSwitcher` component in resources/js/app.js — both read the same
        storage key and treat `system` as `prefers-color-scheme`.
    --}}
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
    <div
        x-data="{ mobileNav: false }"
        x-effect="document.body.style.overflow = mobileNav ? 'hidden' : ''"
        x-on:keydown.escape.window="mobileNav = false"
        class="min-h-screen overflow-x-clip"
    >
        {{-- Desktop sidebar --}}
        <aside class="hidden lg:block">
            <div class="fixed inset-y-0 left-0 z-40 w-sidebar">
                @include('partials.sidebar')
            </div>
        </aside>

        {{-- Mobile drawer --}}
        <div
            x-show="mobileNav"
            x-cloak
            class="fixed inset-0 z-50 flex lg:hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Navigasi utama"
        >
            <div class="h-full w-sidebar max-w-[85vw] shrink-0 shadow-pop">
                @include('partials.sidebar')
            </div>
            <button
                type="button"
                class="flex-1 bg-navy-950/60"
                aria-label="Tutup menu navigasi"
                x-on:click="mobileNav = false"
            ></button>
        </div>

        <div class="min-w-0 lg:pl-sidebar">
            @include('partials.topbar', ['title' => trim($__env->yieldContent('title', 'Dashboard'))])

            <main class="p-4 lg:p-6">
                @if (session('success'))
                    <x-alert tone="success">{{ session('success') }}</x-alert>
                @endif

                @if (session('error'))
                    <x-alert tone="error">{{ session('error') }}</x-alert>
                @endif

                @yield('content')
            </main>

            <footer class="px-4 pb-6 text-xs text-ink-muted lg:px-6">
                {{ config('app.name') }} · {{ config('app.timezone') }}
            </footer>
        </div>
    </div>
</body>
</html>
