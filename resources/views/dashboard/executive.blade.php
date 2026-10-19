@extends('layouts.app')

@section('title', $role->title())

@section('content')
    @php
        use App\Support\Chart;
    @endphp

    <x-page-header :title="$role->title()" :subtitle="$role->subtitle()" icon="dashboard">
        <x-slot:actions>
            @include('dashboard.partials.filter', ['period' => $period])
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-4 text-xs text-ink-soft">
        Periode {{ $period->label() }} · tampilan baca-saja untuk pengambilan keputusan
    </p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Laporan Eksekutif', 'icon' => 'chart', 'route' => 'reports.index', 'permission' => 'reports.view'],
        ['label' => 'Ekspor Laporan', 'icon' => 'fileText', 'route' => 'reports.export', 'permission' => 'reports.view'],
        ['label' => 'Penjualan', 'icon' => 'trendUp', 'route' => 'reports.index', 'params' => ['type' => 'sales'], 'permission' => 'reports.view'],
        ['label' => 'Pemenuhan Pesanan', 'icon' => 'truck', 'route' => 'shipments.index', 'permission' => 'shipments.view'],
        ['label' => 'Piutang', 'icon' => 'fileText', 'route' => 'receivables.index', 'permission' => 'invoices.view'],
        ['label' => 'Penerimaan', 'icon' => 'inbox', 'route' => 'receipts.index', 'permission' => 'receipts.view'],
        ['label' => 'Rebate', 'icon' => 'percent', 'route' => 'rebates.index', 'permission' => 'rebates.view'],
        ['label' => 'Audit Log', 'icon' => 'shield', 'route' => 'audit.index', 'permission' => 'audit.view'],
    ]])

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header
                title="Tren Penjualan & Pembayaran"
                icon="chart"
                :subtitle="'Invoice dibanding pembayaran terverifikasi · per '.$data['sales_series']['granularity']"
            />
            @if (count($data['sales_series']['labels']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="chart" />
            @else
                <div class="p-4">
                    <x-chart :config="Chart::salesSeries($data['sales_series'])" height="15rem" />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Distribusi Status Pesanan" icon="cart" />
            <div class="p-4">
                @if (collect($data['order_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada pesanan." icon="cart" />
                @else
                    <x-chart :config="Chart::doughnut($data['order_status'])" height="15rem" />
                @endif
            </div>
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Penjualan per Sekolah" icon="school" :subtitle="'Nilai barang terkirim · '.$period->label()" />
            @if (count($data['sales_by_school']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="school" />
            @else
                <div class="p-4">
                    <x-chart
                        :config="Chart::horizontalBars($data['sales_by_school'])"
                        :height="max(170, count($data['sales_by_school']) * 46).'px'"
                    />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Penjualan per Penerbit" icon="book" :subtitle="'Kontribusi terhadap total penjualan terkirim · '.$period->label()" />
            @if (count($data['sales_by_publisher']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="book" />
            @else
                <div class="p-4">
                    <x-chart
                        :config="Chart::horizontalBars($data['sales_by_publisher'], '#8b5cf6')"
                        :height="max(170, count($data['sales_by_publisher']) * 46).'px'"
                    />

                    <ul class="mt-4 space-y-2">
                        @foreach ($data['sales_by_publisher'] as $row)
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="truncate text-ink-soft">{{ $row['label'] }}</span>
                                <span class="whitespace-nowrap text-ink">
                                    <span class="font-medium">{{\App\Support\Format::idr($row['value'])}}</span>
                                    <span class="ml-1.5 text-xs text-ink-muted">{{ number_format($row['share'], 1, ',', '.') }}%</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header
            title="Progres Pemenuhan"
            icon="bolt"
            subtitle="Setiap tahap dihitung dari entitas berbeda, bukan penjumlahan pesanan unik"
        />

        @include('dashboard.partials.pipeline', ['steps' => $data['pipeline']])
    </x-card>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card>
            <x-card-header title="Status Invoice" icon="receipt" />
            <div class="p-4">
                @if (collect($data['invoice_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada invoice." icon="receipt" />
                @else
                    <x-chart :config="Chart::doughnut($data['invoice_status'])" height="15rem" />
                @endif
            </div>
        </x-card>

        <x-card class="xl:col-span-2">
            <x-card-header title="Umur Piutang" icon="clock" subtitle="Piutang adalah kondisi saat ini, bukan angka periode" />
            @include('dashboard.partials.aging-table', ['rows' => $data['aging']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header title="Piutang Jatuh Tempo" icon="alert" :subtitle="$data['rebate_counts']['submitted'].' rebate menunggu · '.$data['rebate_counts']['approved'].' disetujui'">
                <x-slot:action>
                    <a href="{{ route('receivables.index', ['bucket' => 'overdue']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.overdue-invoice-table', ['rows' => $data['overdue_invoices']])
        </x-card>

        <x-card>
            <x-card-header title="Risiko Stok" icon="boxes" subtitle="Ketersediaan terendah lebih dahulu">
                <x-slot:action>
                    <a href="{{ route('receipts.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat penerimaan</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.stock-table', ['rows' => $data['stock_rows']])
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Pengecualian Operasional" icon="bell" :subtitle="count($data['notifications']).' hal yang perlu perhatian'" />

        @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
    </x-card>
@endsection
