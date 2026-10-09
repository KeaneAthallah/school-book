@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        use App\Support\Format;

        $cards = [
            ['key' => 'orders_received', 'label' => 'Pesanan Masuk', 'icon' => 'cart', 'iconTone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300'],
            ['key' => 'ready_picking', 'label' => 'Siap Picking', 'icon' => 'clipboard', 'iconTone' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300', 'hint' => 'kondisi saat ini'],
            ['key' => 'awaiting_stock', 'label' => 'Menunggu Stok', 'icon' => 'clock', 'iconTone' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300', 'tone' => 'text-amber-600 dark:text-amber-400', 'hint' => 'kondisi saat ini'],
            ['key' => 'in_delivery', 'label' => 'Dalam Pengiriman', 'icon' => 'truck', 'iconTone' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300', 'hint' => 'kondisi saat ini'],
            ['key' => 'total_invoice', 'label' => 'Total Invoice', 'icon' => 'receipt', 'iconTone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300'],
            ['key' => 'paid', 'label' => 'Sudah Dibayar', 'icon' => 'wallet', 'iconTone' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300', 'tone' => 'text-emerald-700 dark:text-emerald-400'],
            ['key' => 'receivable', 'label' => 'Piutang', 'icon' => 'fileText', 'iconTone' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300', 'tone' => 'text-amber-600 dark:text-amber-400', 'hint' => 'seluruh invoice'],
            ['key' => 'overdue', 'label' => 'Jatuh Tempo', 'icon' => 'alert', 'iconTone' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300', 'tone' => 'text-rose-600 dark:text-rose-400', 'goodWhenDown' => true],
        ];

        $series = $report['sales_series'];
        $salesConfig = [
            'type' => 'line',
            'currency' => true,
            'legend' => true,
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [
                    [
                        'label' => 'Invoice',
                        'data' => $series['invoiced'],
                        'borderColor' => '#2560e8',
                        'backgroundColor' => 'rgba(37, 96, 232, 0.16)',
                        'fill' => true,
                        'tension' => 0.35,
                        'pointRadius' => 2.5,
                        'pointHoverRadius' => 5,
                        'borderWidth' => 2.4,
                    ],
                    [
                        'label' => 'Pembayaran',
                        'data' => $series['paid'],
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.14)',
                        'fill' => true,
                        'tension' => 0.35,
                        'pointRadius' => 2.5,
                        'pointHoverRadius' => 5,
                        'borderWidth' => 2.4,
                    ],
                ],
            ],
        ];

        $orderStatusConfig = [
            'type' => 'doughnut',
            'cutout' => '62%',
            'legend' => true,
            'data' => [
                'labels' => array_column($report['order_status'], 'label'),
                'datasets' => [[
                    'data' => array_column($report['order_status'], 'value'),
                    'backgroundColor' => array_column($report['order_status'], 'color'),
                    'borderWidth' => 2,
                ]],
            ],
        ];

        $invoiceStatusConfig = [
            'type' => 'doughnut',
            'cutout' => '62%',
            'legend' => true,
            'data' => [
                'labels' => array_column($report['invoice_status'], 'label'),
                'datasets' => [[
                    'data' => array_column($report['invoice_status'], 'value'),
                    'backgroundColor' => array_column($report['invoice_status'], 'color'),
                    'borderWidth' => 2,
                ]],
            ],
        ];

        $schoolConfig = [
            'type' => 'bar',
            'horizontal' => true,
            'currency' => true,
            'legend' => false,
            'data' => [
                'labels' => array_column($report['sales_by_school'], 'label'),
                'datasets' => [[
                    'label' => 'Penjualan',
                    'data' => array_column($report['sales_by_school'], 'value'),
                    'backgroundColor' => '#2560e8',
                    'borderRadius' => 6,
                    'barThickness' => 16,
                ]],
            ],
        ];

        $publisherConfig = [
            'type' => 'bar',
            'horizontal' => true,
            'currency' => true,
            'legend' => false,
            'data' => [
                'labels' => array_column($report['sales_by_publisher'], 'label'),
                'datasets' => [[
                    'label' => 'Penjualan',
                    'data' => array_column($report['sales_by_publisher'], 'value'),
                    'backgroundColor' => '#8b5cf6',
                    'borderRadius' => 6,
                    'barThickness' => 16,
                ]],
            ],
        ];
    @endphp

    <x-page-header
        title="Dashboard"
        subtitle="Ringkasan aktivitas penjualan dan distribusi buku sekolah."
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('dashboard') }}" class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                <x-field label="Dari" class="w-full min-[420px]:w-36">
                    <x-input type="date" name="date_from" value="{{ $report['range']['from'] }}" aria-label="Tanggal awal" />
                </x-field>
                <x-field label="Sampai" class="w-full min-[420px]:w-36">
                    <x-input type="date" name="date_to" value="{{ $report['range']['to'] }}" aria-label="Tanggal akhir" />
                </x-field>
                <x-button type="submit" variant="secondary">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan
                </x-button>
                <x-button
                    :href="route('dashboard', ['date_from' => now()->subDays(6)->toDateString(), 'date_to' => now()->toDateString()])"
                    variant="ghost"
                    size="sm"
                >7 hari</x-button>
                <x-button
                    :href="route('dashboard', ['date_from' => now()->subDays(29)->toDateString(), 'date_to' => now()->toDateString()])"
                    variant="ghost"
                    size="sm"
                >30 hari</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-4 text-xs text-ink-soft">
        Periode {{ $report['range']['label'] }} · metrik periode dibandingkan dengan periode sebelumnya
    </p>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-3 min-[400px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($cards as $card)
            @php $kpi = $report['kpis'][$card['key']] ?? null; @endphp
            @continue($kpi === null)

            <x-stat-card
                :label="$card['label']"
                :icon="$card['icon']"
                :icon-tone="$card['iconTone']"
                :tone="$card['tone'] ?? 'text-ink'"
                :value="$kpi['format'] === 'currency' ? Format::idr($kpi['value']) : Format::number($kpi['value'])"
                :delta="$kpi['delta']"
                :good-when-down="$card['goodWhenDown'] ?? false"
                :hint="$card['key'] === 'overdue' && ($kpi['count'] ?? 0) > 0 ? Format::number($kpi['count']).' invoice' : ($card['hint'] ?? null)"
            />
        @endforeach
    </div>

    {{-- Sales chart + order status --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header
                title="Grafik Penjualan (Invoice)"
                icon="chart"
                :subtitle="'Nilai invoice diterbitkan dibanding pembayaran terverifikasi · per '.$series['granularity']"
            />
            @if (count($series['labels']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="chart" />
            @else
                <div class="p-4">
                    <x-chart :config="$salesConfig" height="15rem" />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Status Pesanan" icon="cart" />
            <div class="p-4">
                @if (collect($report['order_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada pesanan." icon="cart" />
                @else
                    <x-chart :config="$orderStatusConfig" height="15rem" />
                @endif
            </div>
        </x-card>
    </div>

    {{-- Invoice status + fulfillment progress --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card>
            <x-card-header title="Status Invoice" icon="receipt" />
            <div class="p-4">
                @if (collect($report['invoice_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada invoice." icon="receipt" />
                @else
                    <x-chart :config="$invoiceStatusConfig" height="15rem" />
                @endif
            </div>
        </x-card>

        <x-card class="xl:col-span-2">
            <x-card-header
                title="Progres Pemenuhan Pesanan"
                icon="bolt"
                subtitle="Setiap tahap dihitung dari entitas berbeda, bukan penjumlahan pesanan unik"
            />
            <ol class="flex flex-col gap-3 p-4 sm:flex-row sm:items-stretch sm:gap-2">
                @foreach ($report['fulfillment'] as $step)
                    <li class="relative flex flex-1 items-center gap-3 sm:flex-col sm:gap-2">
                        <div class="flex w-full items-center sm:justify-center">
                            @if (! $loop->first)
                                <span class="mr-3 hidden h-0.5 flex-1 bg-line sm:block" aria-hidden="true"></span>
                            @endif
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $step['tone'] }}">
                                <x-icon :name="$step['icon']" class="h-5 w-5" />
                            </span>
                            @if (! $loop->last)
                                <span class="ml-3 h-0.5 flex-1 bg-line sm:hidden" aria-hidden="true"></span>
                            @endif
                        </div>
                        <div class="text-left sm:w-full sm:text-center">
                            <p class="text-lg font-semibold text-ink tabular-nums">{{ Format::number($step['value']) }}</p>
                            <p class="text-xs text-ink-soft">{{ $step['label'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    {{-- Recent orders + top books --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Pesanan Terbaru" icon="clipboard">
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            <x-data-table
                empty="Belum ada pesanan."
                empty-icon="cart"
                :rows="$report['recent_orders']"
                :columns="[
                    ['key' => 'number', 'label' => 'No. Pesanan', 'link' => 'orders.show'],
                    ['key' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
                    ['key' => 'school', 'label' => 'Sekolah'],
                    ['key' => 'source', 'label' => 'Sumber'],
                    ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                    ['key' => 'total', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Stok Buku (Top 5)" icon="book" subtitle="Diurutkan dari ketersediaan terendah">
                <x-slot:action>
                    <a href="{{ route('stock.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat stok</a>
                </x-slot:action>
            </x-card-header>

            <x-data-table
                empty="Belum ada stok tercatat."
                empty-icon="book"
                :rows="$report['top_books']"
                :columns="[
                    ['label' => 'Judul Buku', 'view' => 'dashboard.partials.top-book'],
                    ['key' => 'available', 'label' => 'Tersedia', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['label' => 'Status', 'view' => 'dashboard.partials.top-book-status'],
                ]"
            />
        </x-card>
    </div>

    {{-- Overdue receivables + today shipments --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Piutang Jatuh Tempo" icon="alert">
                <x-slot:action>
                    <a href="{{ route('receivables.index', ['bucket' => 'overdue']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            <x-data-table
                empty="Tidak ada piutang jatuh tempo."
                empty-icon="checkCircle"
                :rows="$report['overdue_receivables']"
                :columns="[
                    ['key' => 'school', 'label' => 'Sekolah'],
                    ['key' => 'number', 'label' => 'Invoice', 'link' => 'invoices.show'],
                    ['key' => 'outstanding', 'label' => 'Nilai Piutang', 'type' => 'currency', 'align' => 'right'],
                    ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                    ['label' => 'Status', 'view' => 'dashboard.partials.overdue-days'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Pengiriman Hari Ini" icon="truck" />

            <x-data-table
                empty="Belum ada pengiriman hari ini."
                empty-icon="truck"
                :rows="$report['today_shipments']"
                :columns="[
                    ['key' => 'number', 'label' => 'No. Pengiriman', 'link' => 'shipments.show'],
                    ['key' => 'school', 'label' => 'Sekolah'],
                    ['key' => 'quantity', 'label' => 'Jumlah Buku', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ]"
            />
        </x-card>
    </div>

    {{-- Notifications + activity --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header title="Notifikasi & Peringatan" icon="bell" subtitle="Dihitung dari data operasional terkini" />

            @if (count($notifications) === 0)
                <x-empty-state message="Tidak ada peringatan yang perlu ditangani." icon="checkCircle" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($notifications as $alert)
                        <li>
                            <a href="{{ $alert['href'] ?? '#' }}" class="flex items-start gap-3 px-4 py-3 transition hover:bg-panel-soft">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-control {{ ['danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300', 'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300', 'info' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300'][$alert['tone']] ?? 'bg-panel-soft text-ink-muted' }}">
                                    <x-icon :name="['danger' => 'alert', 'warning' => 'clock'][$alert['tone']] ?? 'bell'" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-ink">{{ $alert['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-ink-soft">{{ $alert['description'] }}</p>
                                </div>
                                @if ($alert['time'])
                                    <span class="hidden text-[11px] whitespace-nowrap text-ink-muted sm:block">{{ Format::dateTime($alert['time']) }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Aktivitas Terbaru" icon="clock" />

            @if (count($report['recent_activity']) === 0)
                <x-empty-state message="Belum ada aktivitas tercatat." icon="clock" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($report['recent_activity'] as $activity)
                        <li class="px-4 py-3">
                            <p class="text-sm text-ink">{{ $activity['description'] ?? $activity['action'] }}</p>
                            <p class="mt-0.5 text-[11px] text-ink-muted">
                                {{ $activity['user'] ?? 'Sistem' }} · {{ Format::dateTime($activity['time']) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    {{-- Sales by school + publisher --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Penjualan per Sekolah" icon="school" :subtitle="'Nilai buku terkirim · '.$report['range']['label']" />
            @if (count($report['sales_by_school']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="school" />
            @else
                <div class="p-4">
                    <x-chart :config="$schoolConfig" :height="max(170, count($report['sales_by_school']) * 46).'px'" />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Penjualan per Penerbit" icon="book" :subtitle="'Kontribusi terhadap total penjualan terkirim · '.$report['range']['label']" />
            @if (count($report['sales_by_publisher']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="book" />
            @else
                <div class="p-4">
                    <x-chart :config="$publisherConfig" :height="max(170, count($report['sales_by_publisher']) * 46).'px'" />

                    <ul class="mt-4 space-y-2">
                        @foreach ($report['sales_by_publisher'] as $row)
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="truncate text-ink-soft">{{ $row['label'] }}</span>
                                <span class="whitespace-nowrap text-ink">
                                    <span class="font-medium">{{ Format::idr($row['value']) }}</span>
                                    <span class="ml-1.5 text-xs text-ink-muted">{{ number_format($row['share'], 1, ',', '.') }}%</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-card>
    </div>
@endsection
