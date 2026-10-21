@extends('layouts.app')

@section('title', 'Picking & Packing')

@section('content')
    @php
        $activeStatus = collect($statuses)->firstWhere('value', $filters['status']);
    @endphp

    <x-page-header
        title="Picking & Packing"
        :subtitle="$activeStatus
            ? 'Menampilkan tugas dengan status '.$activeStatus['label'].'.'
            : 'Daftar tugas pengambilan dan pengepakan barang.'"
        icon="clipboard"
    />

    <x-card>
        <x-filter-bar method="get" :action="route('pickings.index')">
            <x-field label="Status" class="w-52">
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
            empty="Belum ada tugas picking."
            empty-icon="clipboard"
            :rows="$pickings"
            :columns="[
                ['key' => 'number', 'label' => 'No. Picking', 'link' => 'pickings.show'],
                ['key' => 'order', 'label' => 'Pesanan'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'items_count', 'label' => 'Item', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ['key' => 'created_at', 'label' => 'Dibuat', 'type' => 'datetime'],
                ['label' => 'Aksi', 'view' => 'inventory.pickings.partials.delete-action'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $pickings->links() }}</div>
@endsection
