@extends('layouts.app')

@section('title', 'Pesanan')

@section('content')
    @php
        $activeSource = collect($sources)->firstWhere('value', $filters['source']);
    @endphp

    <x-page-header
        title="Pesanan"
        :subtitle="$activeSource
            ? 'Pesanan dari sumber '.$activeSource['label'].'.'
            : 'Daftar pesanan dari semua sumber.'"
    >
        <x-slot:actions>
            @if ($can['create'])
                <x-button :href="route('orders.create')">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Pesanan
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('orders.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nomor / sekolah…" />
            </x-field>
            <x-field label="Status" class="w-48">
                <x-select name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['status'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Sumber" class="w-44">
                <x-select name="source">
                    <option value="">Semua sumber</option>
                    @foreach ($sources as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['source'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada pesanan."
            empty-icon="cart"
            :rows="$orders"
            :columns="[
                ['key' => 'number', 'label' => 'No. Pesanan', 'link' => 'orders.show'],
                ['key' => 'order_date', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'source', 'label' => 'Sumber'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ['key' => 'items_count', 'label' => 'Item', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'total_amount', 'label' => 'Total', 'type' => 'currency', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
