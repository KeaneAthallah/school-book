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
        Periode {{ $period->label() }} · metrik periode dibandingkan dengan periode sebelumnya
    </p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Kelola Pengguna', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.view'],
        ['label' => 'Peran & Izin', 'icon' => 'shield', 'route' => 'roles.index', 'permission' => 'roles.manage'],
        ['label' => 'Data Sekolah', 'icon' => 'school', 'route' => 'master.index', 'params' => ['resource' => 'schools'], 'permission' => 'schools.view'],
        ['label' => 'Data Penerbit', 'icon' => 'database', 'route' => 'master.index', 'params' => ['resource' => 'publishers'], 'permission' => 'publishers.view'],
        ['label' => 'Data Buku', 'icon' => 'book', 'route' => 'master.index', 'params' => ['resource' => 'books'], 'permission' => 'books.view'],
        ['label' => 'Konfigurasi', 'icon' => 'settings', 'route' => 'master.index', 'params' => ['resource' => 'settings'], 'permission' => 'settings.manage'],
        ['label' => 'Audit Log', 'icon' => 'fileText', 'route' => 'audit.index', 'permission' => 'audit.view'],
        ['label' => 'Integrasi', 'icon' => 'bolt', 'route' => 'integrations.index', 'permission' => 'integrations.view'],
        ['label' => 'Laporan', 'icon' => 'chart', 'route' => 'reports.index', 'permission' => 'reports.view'],
    ]])

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header
                title="Penjualan & Pembayaran"
                icon="chart"
                :subtitle="'Invoice diterbitkan dibanding pembayaran terverifikasi · per '.$data['sales_series']['granularity']"
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
            <x-card-header title="Status Pesanan" icon="cart" />
            <div class="p-4">
                @if (collect($data['order_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada pesanan." icon="cart" />
                @else
                    <x-chart :config="Chart::doughnut($data['order_status'])" height="15rem" />
                @endif
            </div>
        </x-card>
    </div>

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
            <x-card-header title="Pengguna & Peran" icon="users" subtitle="Jumlah pengguna dan izin pada setiap peran" />

            <x-data-table
                empty="Belum ada peran."
                empty-icon="shield"
                :rows="$data['role_breakdown']"
                :columns="[
                    ['key' => 'name', 'label' => 'Peran'],
                    ['key' => 'slug', 'label' => 'Kode'],
                    ['key' => 'users', 'label' => 'Pengguna', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'permissions', 'label' => 'Izin', 'align' => 'right', 'class' => 'tabular-nums'],
                ]"
            />
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Pesanan Terbaru" icon="clipboard">
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['recent_orders']])
        </x-card>

        <x-card>
            <x-card-header title="Stok Barang" icon="book" subtitle="Diurutkan dari ketersediaan terendah">
                <x-slot:action>
                    <a href="{{ route('stock.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat stok</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.stock-table', ['rows' => $data['stock_rows']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Piutang Jatuh Tempo" icon="alert">
                <x-slot:action>
                    <a href="{{ route('receivables.index', ['bucket' => 'overdue']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.overdue-invoice-table', ['rows' => $data['overdue_invoices']])
        </x-card>

        <x-card>
            <x-card-header
                title="Peringatan Sistem"
                icon="bell"
                :subtitle="count($data['notifications']).' peringatan · '.$data['open_backorders'].' back order · '.$data['payments_to_verify'].' pembayaran belum diverifikasi'"
            />

            @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Aktivitas Sistem Terbaru" icon="clock" />

        @include('dashboard.partials.activity', ['activity' => $data['recent_activity']])
    </x-card>
@endsection
