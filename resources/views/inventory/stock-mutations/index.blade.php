@extends('layouts.app')
@section('title', 'Mutasi Stok')
@section('content')
    <x-page-header title="Mutasi Stok" subtitle="Pencatatan buku hilang, rusak, atau dipinjam." icon="refresh" />
    <x-card class="mb-4">
        <x-card-header title="Catat Mutasi" icon="refresh" />
        <form method="POST" action="{{ route('stock-mutations.store') }}" class="grid gap-3 p-4 sm:grid-cols-4">
            @csrf
            <x-field label="Barang" required>
                <x-select name="product_id">
                    @foreach ($products as $p)
                        <option value="{{ $p['value'] }}">{{ $p['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Jenis" required>
                <x-select name="type">
                    <option value="lost">Hilang</option>
                    <option value="damaged">Rusak</option>
                    <option value="borrowed">Dipinjam</option>
                </x-select>
            </x-field>
            <x-field label="Jumlah" required>
                <x-input type="number" name="quantity" min="1" value="1" />
            </x-field>
            <x-field label="Alasan">
                <x-input type="text" name="reason" placeholder="opsional" />
            </x-field>
            <div class="sm:col-span-4 flex justify-end">
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        <x-card-header title="Riwayat Mutasi Stok" icon="refresh" />
        <x-data-table
            empty="Belum ada mutasi stok."
            empty-icon="refresh"
            :rows="$mutations"
            :columns="[
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'type_label', 'label' => 'Jenis'],
                ['key' => 'quantity', 'label' => 'Jumlah', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'reason', 'label' => 'Alasan'],
                ['key' => 'creator', 'label' => 'Dicatat oleh'],
                ['key' => 'date', 'label' => 'Tanggal'],
                ['label' => 'Aksi', 'view' => 'inventory.stock-mutations.partials.print-action'],
            ]"
        />
    </x-card>
    <div class="mt-3">{{ $mutations->links() }}</div>
@endsection