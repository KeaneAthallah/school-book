<!DOCTYPE html>
<html lang="id" data-theme="light" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Masuk ke {{ config('app.name') }} — sistem manajemen operasional dan keuangan CV. Prana Media.">

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
<body class="bg-canvas text-ink antialiased">
    <div class="flex min-h-screen">
        {{-- Brand panel — navy in dark mode, tinted paper in light mode. --}}
        <aside class="relative hidden w-[46%] shrink-0 overflow-hidden bg-brand-50 dark:bg-navy-950 lg:flex lg:flex-col">
            <div class="absolute inset-0 bg-gradient-to-br from-white via-brand-50 to-brand-100 dark:from-navy-800 dark:via-navy-900 dark:to-navy-950"></div>
            <div class="brand-grid absolute inset-0"></div>
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-600/15 blur-[120px] dark:bg-brand-600/20"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-brand-500/10 blur-[100px]"></div>

            <div class="relative z-10 flex h-full flex-col p-10 xl:p-12">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-line dark:bg-white/10 dark:ring-white/15">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-12 object-cover">
                    </span>
                    <div>
                        <p class="text-base font-semibold text-ink dark:text-white">{{ config('app.name') }}</p>
                        <p class="text-xs text-ink-soft dark:text-navy-200/80">Kelola Lebih Mudah, Pantau Lebih Akurat.</p>
                    </div>
                </div>

                <div class="mt-auto max-w-md pb-10">
                    <h2 class="text-2xl font-semibold leading-snug tracking-tight text-ink dark:text-white xl:text-[1.75rem]">
                        Rekapitulasi, kendali, dan analisis — dalam satu tempat.
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-soft dark:text-navy-200/85">
                        Catat pemasukan dan pengeluaran, kelola stok buku, susun laporan keuangan, dan pantau performa penjualan secara terintegrasi.
                    </p>

                    <ul class="mt-9 space-y-5">
                        <li class="flex items-start gap-3.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-white ring-1 ring-line text-brand-600 dark:bg-white/10 dark:ring-white/10 dark:text-brand-300">
                                <x-icon name="chart" class="h-[18px] w-[18px]" />
                            </span>
                            <span>
                                <span class="block text-sm font-medium text-ink dark:text-white">Analisis keuangan terintegrasi</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-ink-soft dark:text-navy-200/75">Dashboard piutang, pembayaran, dan rebate dalam satu pandangan.</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-3.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-white ring-1 ring-line text-brand-600 dark:bg-white/10 dark:ring-white/10 dark:text-brand-300">
                                <x-icon name="book" class="h-[18px] w-[18px]" />
                            </span>
                            <span>
                                <span class="block text-sm font-medium text-ink dark:text-white">Stok & penjualan buku</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-ink-soft dark:text-navy-200/75">Penerimaan barang, picking, dan pengiriman tercatat rapi.</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-3.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-white ring-1 ring-line text-brand-600 dark:bg-white/10 dark:ring-white/10 dark:text-brand-300">
                                <x-icon name="shield" class="h-[18px] w-[18px]" />
                            </span>
                            <span>
                                <span class="block text-sm font-medium text-ink dark:text-white">Ter Kendali &amp; auditable</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-ink-soft dark:text-navy-200/75">Peran pengguna dan jejak audit yang jelas untuk setiap transaksi.</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-ink-muted dark:text-navy-200/60">© {{ date('Y') }} CV. Prana Media. Hak cipta dilindungi.</p>
            </div>
        </aside>

        {{-- Form panel --}}
        <div class="relative flex min-w-0 flex-1 flex-col">
            <div class="absolute right-4 top-4 z-20 sm:right-5 sm:top-5">
                <x-theme-switcher />
            </div>

            <main class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-8">
                {{-- Compact brand header (mobile) --}}
                <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-control bg-brand-600">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-10 object-cover">
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ config('app.name') }}</p>
                        <p class="text-[11px] text-ink-soft">Kelola Lebih Mudah, Pantau Lebih Akurat.</p>
                    </div>
                </div>

                <div class="w-full max-w-sm">
                    @yield('content')

                    <p class="mt-8 text-center text-xs text-ink-soft lg:hidden">
                        © {{ date('Y') }} CV. Prana Media
                    </p>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
