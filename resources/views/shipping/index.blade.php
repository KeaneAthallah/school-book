@extends('layouts.app')

@section('title', 'Pengiriman')

@section('content')
    @php
        $activeStatus = collect($statuses)->firstWhere('value', $filters['status']);
    @endphp

    <x-page-header
        title="Pengiriman"
        :subtitle="$activeStatus
            ? 'Menampilkan pengiriman dengan status '.$activeStatus['label'].'.'
            : 'Surat jalan dan pengiriman barang ke sekolah.'"
        icon="truck"
    >
        <x-slot:actions>
            @if ($can['create'])
                <x-button :href="route('shipments.create')">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Pengiriman
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('shipments.index')">
            <x-field label="Cari" class="w-60">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="No. pengiriman / surat jalan" />
            </x-field>
            <x-field label="Status" class="w-48">
                <x-select name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['status'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada pengiriman."
            empty-icon="truck"
            :rows="$shipments"
            :columns="[
                ['key' => 'number', 'label' => 'No. Pengiriman', 'link' => 'shipments.show'],
                ['key' => 'delivery_note_number', 'label' => 'Surat Jalan'],
                ['key' => 'order', 'label' => 'Pesanan', 'link' => 'orders.show', 'linkKey' => 'order_id'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'courier', 'label' => 'Kurir'],
                ['key' => 'ship_date', 'label' => 'Tgl Kirim', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $shipments->links() }}</div>
@endsection
