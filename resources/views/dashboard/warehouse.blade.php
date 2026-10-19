@extends('layouts.app')

@section('title', $role->title())

@section('content')
    <x-page-header :title="$role->title()" :subtitle="$role->subtitle()" icon="dashboard" />

    <p class="-mb-1 text-xs text-ink-soft">Ringkasan operasional gudang berdasarkan kondisi terkini.</p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" class="mt-3" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Catat Barang Masuk', 'icon' => 'inbox', 'route' => 'receipts.create', 'permission' => 'receipts.manage'],
        ['label' => 'Daftar Penerimaan', 'icon' => 'fileText', 'route' => 'receipts.index', 'permission' => 'receipts.view'],
        ['label' => 'Back Order', 'icon' => 'refresh', 'route' => 'backorders.index', 'permission' => 'procurement.view'],
        ['label' => 'Item Tidak Tersedia', 'icon' => 'alert', 'route' => 'backorders.unavailable', 'permission' => 'procurement.view'],
        ['label' => 'Picking & Packing', 'icon' => 'clipboard', 'route' => 'pickings.index', 'permission' => 'fulfillment.view'],
        ['label' => 'Pengiriman', 'icon' => 'truck', 'route' => 'shipments.index', 'permission' => 'shipments.view'],
    ]])

    <x-card class="mt-4">
        <x-card-header
            title="Progres Pemenuhan"
            icon="bolt"
            subtitle="Setiap tahap dihitung dari entitas berbeda, bukan penjumlahan pesanan unik"
        />

        @include('dashboard.partials.pipeline', ['steps' => $data['pipeline']])
    </x-card>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Antrean Picking & Packing" icon="clipboard" subtitle="Diurutkan dari yang paling lama menunggu">
                <x-slot:action>
                    <a href="{{ route('pickings.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.picking-table', ['rows' => $data['pickup_queue']])
        </x-card>

        <x-card>
            <x-card-header title="Stok Menipis & Habis" icon="alert" subtitle="Ketersediaan terendah lebih dahulu">
                <x-slot:action>
                    <a href="{{ route('receipts.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Kelola penerimaan</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.stock-table', ['rows' => $data['stock_rows']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Penerimaan Barang Terbaru" icon="inbox">
                <x-slot:action>
                    <a href="{{ route('receipts.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.receipt-table', ['rows' => $data['recent_receipts']])
        </x-card>

        <x-card>
            <x-card-header title="Back Order" icon="refresh" subtitle="Menunggu stok masuk dari penerbit">
                <x-slot:action>
                    <a href="{{ route('reports.index', ['type' => 'backorders']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat laporan</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.backorder-table', ['rows' => $data['backorders']])
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Mutasi Stok Terbaru" icon="refresh" subtitle="Seluruh pergerakan stok mengikuti aturan reservasi dan pengiriman yang berlaku">
            <x-slot:action>
                <a href="{{ route('receipts.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
            </x-slot:action>
        </x-card-header>

        <x-data-table
            empty="Belum ada mutasi stok."
            empty-icon="refresh"
            :rows="$data['stock_movements']"
            :columns="[
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'type', 'label' => 'Jenis'],
                ['key' => 'quantity', 'label' => 'Jumlah', 'align' => 'right', 'class' => 'tabular-nums'],
                ['label' => 'Waktu', 'view' => 'dashboard.partials.movement-time'],
            ]"
        />
    </x-card>

    <x-card class="mt-4">
        <x-card-header title="Peringatan Gudang" icon="bell" :subtitle="count($data['notifications']).' hal yang perlu ditangani'" />

        @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
    </x-card>
@endsection
