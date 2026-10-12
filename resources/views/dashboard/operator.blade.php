@extends('layouts.app')

@section('title', $role->title())

@section('content')
    @php
        use App\Support\Chart;
    @endphp

    <x-page-header :title="$role->title()" :subtitle="$role->subtitle()">
        <x-slot:actions>
            @include('dashboard.partials.filter', ['period' => $period])
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-4 text-xs text-ink-soft">
        Periode {{ $period->label() }} · antrean di bawah adalah kondisi saat ini
    </p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Buat Pesanan Manual', 'icon' => 'plus', 'route' => 'orders.create', 'permission' => 'orders.manage'],
        ['label' => 'Antrean Pesanan', 'icon' => 'cart', 'route' => 'orders.index', 'permission' => 'orders.view'],
        ['label' => 'Import SIPLah', 'icon' => 'bolt', 'route' => 'integrations.index', 'permission' => 'integrations.view'],
        ['label' => 'e-Katalog', 'icon' => 'refresh', 'route' => 'integrations.index', 'permission' => 'integrations.view'],
        ['label' => 'Cek Stok', 'icon' => 'boxes', 'route' => 'stock.index', 'permission' => 'stock.view'],
        ['label' => 'Laporan', 'icon' => 'chart', 'route' => 'reports.index', 'permission' => 'reports.view'],
    ]])

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
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

        <x-card class="xl:col-span-2">
            <x-card-header title="Pesanan Masuk Terbaru" icon="clipboard" :subtitle="'Dibuat pada '.$period->label()">
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['recent_orders']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header
                title="Perlu Validasi"
                icon="alert"
                :subtitle="'Pesanan dari integrasi yang belum dikonfirmasi'"
            >
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Buka antrean</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['validation_queue'], 'empty' => 'Tidak ada pesanan menunggu validasi.'])
        </x-card>

        <x-card>
            <x-card-header title="Menunggu Stok" icon="clock" subtitle="Sudah dikonfirmasi, menunggu ketersediaan buku">
                <x-slot:action>
                    <a href="{{ route('stock.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Cek stok</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['waiting_stock_queue'], 'empty' => 'Tidak ada pesanan menunggu stok.'])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Siap Picking" icon="clipboard" subtitle="Stok sudah dialokasikan untuk pesanan ini">
                <x-slot:action>
                    <a href="{{ route('pickings.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Buka picking</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['ready_queue'], 'empty' => 'Belum ada pesanan siap picking.'])
        </x-card>

        <x-card>
            <x-card-header title="Back Order" icon="inbox" subtitle="Kekurangan stok yang menunggu buku masuk">
                <x-slot:action>
                    <a href="{{ route('reports.index', ['type' => 'backorders']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat laporan</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.backorder-table', ['rows' => $data['backorders']])
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Peringatan Operasional" icon="bell" :subtitle="count($data['notifications']).' hal yang perlu ditangani'" />

        @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
    </x-card>
@endsection
