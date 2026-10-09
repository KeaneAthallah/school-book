@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
    <x-page-header
        title="Penerimaan Barang"
        subtitle="Penerimaan buku dari penerbit. Stok bertambah setelah dikonfirmasi."
    >
        <x-slot:actions>
            @if ($can['create'])
                <x-button :href="route('receipts.create')">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Penerimaan
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('receipts.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nomor penerimaan…" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada penerimaan barang."
            empty-icon="inbox"
            :rows="$receipts"
            :columns="[
                ['key' => 'number', 'label' => 'No. Penerimaan', 'link' => 'receipts.show'],
                ['key' => 'receipt_date', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'publisher', 'label' => 'Penerbit'],
                ['key' => 'warehouse', 'label' => 'Gudang'],
                ['key' => 'items_count', 'label' => 'Item', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $receipts->links() }}</div>
@endsection
