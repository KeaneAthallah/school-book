@extends('layouts.app')

@section('title', 'Rangkuman Stok')

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        title="Rangkuman Stok"
        subtitle="Barang masuk dari stok masuk, barang keluar dari pengiriman, dan sisa stok gudang."
        icon="boxes"
    />

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Barang Masuk" :value="Format::number($summary['masuk'])" icon="inbox" chip-tone="emerald" tone="text-emerald-700 dark:text-emerald-400" />
        <x-stat-card label="Total Barang Keluar" :value="Format::number($summary['keluar'])" icon="truck" chip-tone="cyan" />
        <x-stat-card label="Mutasi (Hilang/Rusak/Dipinjam)" :value="Format::number($summary['mutasi'])" icon="refresh" chip-tone="amber" tone="text-amber-600 dark:text-amber-400" />
        <x-stat-card label="Sisa Stok Gudang" :value="Format::number($summary['sisa'])" icon="boxes" chip-tone="rose" tone="text-rose-600 dark:text-rose-400" :hint="Format::idr($nilai_sisa)" />
    </div>

    <x-card class="mt-4">
        <x-filter-bar method="get" :action="route('stock-summary.index')">
            <x-field label="Cari" class="w-60">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Kode / nama barang" />
            </x-field>
            <x-field label="Sisa Stok" class="w-44">
                <x-select name="filter">
                    <option value="">Semua</option>
                    <option value="tersedia" @selected($filters['filter'] === 'tersedia')">Masih tersedia</option>
                    <option value="habis" @selected($filters['filter'] === 'habis')>Habis / minus</option>
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada barang yang bergerak di gudang."
            empty-icon="boxes"
            :rows="$rows"
            :columns="[
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'title', 'label' => 'Barang'],
                ['key' => 'masuk', 'label' => 'Masuk', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'keluar', 'label' => 'Keluar', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'mutasi', 'label' => 'Mutasi', 'align' => 'right', 'class' => 'tabular-nums'],
                ['label' => 'Sisa Stok', 'view' => 'inventory.stock-summary.partials.remaining', 'align' => 'right'],
                ['key' => 'nilai_sisa', 'label' => 'Nilai Sisa', 'type' => 'currency', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $rows->links() }}</div>
@endsection
