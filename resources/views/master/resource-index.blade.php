@extends('layouts.app')

@section('title', $resource['title'])

@section('content')
    @php
        $tableColumns = [];

        foreach ($resource['columns'] as $column) {
            $tableColumns[] = [
                'key' => 'display.'.$column['key'],
                'label' => $column['label'],
                'type' => $column['type'] ?? 'text',
            ];
        }

        if ($resource['can']['manage']) {
            $tableColumns[] = [
                'label' => 'Aksi',
                'view' => 'master.partials.row-actions',
                'align' => 'right',
            ];
        }
    @endphp

    <x-page-header :title="$resource['title']" :subtitle="'Kelola data '.strtolower($resource['title']).'.'">
        <x-slot:actions>
            @if ($resource['can']['manage'])
                <x-button :href="route('master.create', $resource['slug'])">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('master.index', $resource['slug'])">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Kata kunci…" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            :columns="$tableColumns"
            :rows="$items"
            empty="Belum ada data."
            empty-icon="database"
        />
    </x-card>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
