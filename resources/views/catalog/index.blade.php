@extends('layouts.app')

@section('title', 'Katalog')

@section('content')
    <x-page-header
        title="Katalog"
        subtitle="Daftar katalog buku & barang beserta daftar harganya."
        icon="book"
    >
        <x-slot:actions>
            @if ($can['create'])
                <x-button :href="route('catalogs.create')">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Katalog
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-data-table
            empty="Belum ada katalog."
            empty-icon="book"
            :rows="$catalogs"
            :columns="[
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'description', 'label' => 'Deskripsi'],
                ['key' => 'items_count', 'label' => 'Item', 'align' => 'right', 'class' => 'tabular-nums'],
                ['label' => 'Aksi', 'view' => 'catalog.partials.row-actions', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $catalogs->links() }}</div>
@endsection
