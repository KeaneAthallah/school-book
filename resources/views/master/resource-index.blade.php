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

    <x-page-header :title="$resource['title']" :subtitle="'Kelola data '.strtolower($resource['title']).'.'" icon="database">
        <x-slot:actions>
            @if ($resource['can']['manage'])
                @if ($resource['importable'])
                    <x-button variant="secondary" x-on:click="$dispatch('open-modal', 'import-{{ $resource['slug'] }}')">
                        <x-icon name="upload" class="h-4 w-4" />
                        Impor Excel
                    </x-button>
                    <x-button variant="secondary" :href="route('master.import.template', $resource['slug'])">
                        <x-icon name="download" class="h-4 w-4" />
                        Template
                    </x-button>
                @endif

                <x-button :href="route('master.create', $resource['slug'])">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('import_errors'))
        <x-alert tone="error" class="mb-4">
            <span class="font-medium">Beberapa baris gagal diimpor:</span>
            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                @foreach (session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if ($resource['importable'])
        <x-modal name="import-{{ $resource['slug'] }}" title="Impor {{ $resource['title'] }} dari Excel">
            <form method="post" action="{{ route('master.import', $resource['slug']) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <p class="text-sm text-ink-muted">
                    Unggah file sesuai template. Baris dengan kode yang sudah ada akan diperbarui, kode baru akan ditambahkan.
                </p>

                <x-field label="File Excel" name="file" required hint="Format .xlsx, .xls, atau .csv (maks. 10 MB).">
                    <x-input type="file" name="file" accept=".xlsx,.xls,.csv" required />
                </x-field>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-button variant="ghost" :href="route('master.import.template', $resource['slug'])">
                        <x-icon name="download" class="h-4 w-4" />
                        Unduh template
                    </x-button>

                    <div class="flex items-center gap-2">
                        <x-button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'import-{{ $resource['slug'] }}')">
                            Batal
                        </x-button>
                        <x-button type="submit">
                            <x-icon name="upload" class="h-4 w-4" />
                            Impor
                        </x-button>
                    </div>
                </div>
            </form>
        </x-modal>
    @endif

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
