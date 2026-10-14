@extends('layouts.app')

@section('title', 'Integrasi')

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        title="Integrasi & Import"
        subtitle="Status adaptor sumber pesanan eksternal. Integrasi tanpa kredensial tidak akan mengimpor data."
        icon="bolt"
    />

    <x-card class="mb-4">
        <x-card-header title="Sumber Pesanan" icon="inbox" />
        <x-data-table
            empty="Belum ada sumber pesanan."
            empty-icon="inbox"
            :rows="collect($sources)->map(fn ($source, $index) => ['id' => $index] + $source)->all()"
            :columns="[
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'type_label', 'label' => 'Tipe'],
                ['key' => 'is_active', 'label' => 'Aktif', 'type' => 'boolean'],
                ['label' => 'Integrasi', 'view' => 'integrations.partials.source-state'],
                ['label' => 'Aksi', 'view' => 'integrations.partials.source-actions', 'align' => 'right'],
            ]"
        />
    </x-card>

    <x-card>
        <x-card-header title="Log Integrasi" icon="fileText" />
        <x-data-table
            empty="Belum ada log integrasi."
            empty-icon="fileText"
            :rows="collect($logs)->map(fn ($log, $index) => ['id' => $index] + $log)->all()"
            :columns="[
                ['key' => 'date', 'label' => 'Waktu', 'type' => 'datetime'],
                ['key' => 'source', 'label' => 'Sumber'],
                ['key' => 'direction', 'label' => 'Arah'],
                ['key' => 'reference', 'label' => 'Referensi'],
                ['label' => 'Status', 'view' => 'integrations.partials.log-status'],
                ['key' => 'message', 'label' => 'Pesan'],
            ]"
        />
    </x-card>
@endsection
