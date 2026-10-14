@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
    <x-page-header
        title="Audit Log"
        subtitle="Riwayat tindakan penting pada sistem."
        icon="fileText"
    />

    <x-card>
        <x-filter-bar method="get" :action="route('audit.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Aksi / deskripsi" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada aktivitas tercatat."
            empty-icon="shield"
            :rows="$logs"
            :columns="[
                ['key' => 'date', 'label' => 'Waktu', 'type' => 'datetime'],
                ['key' => 'user', 'label' => 'Pengguna'],
                ['key' => 'action', 'label' => 'Aksi'],
                ['key' => 'description', 'label' => 'Deskripsi'],
                ['key' => 'ip_address', 'label' => 'IP'],
                ['label' => 'Perubahan', 'view' => 'settings.partials.audit-changes'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
