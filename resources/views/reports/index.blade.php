@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <x-page-header
        title="Laporan"
        subtitle="Laporan operasional, penjualan, dan keuangan."
    >
        <x-slot:actions>
            <x-button
                :href="route('reports.export', ['type' => $type, 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']])"
                variant="secondary"
            >
                <x-icon name="fileText" class="h-4 w-4" />
                Export CSV
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <x-filter-bar method="get" :action="route('reports.index')">
            <x-field label="Jenis Laporan" class="w-64">
                <x-select name="type" x-data x-on:change="$el.form.requestSubmit()">
                    @foreach ($types as $option)
                        <option value="{{ $option['value'] }}" @selected($type === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>

            @if (in_array($type, ['sales_by_school', 'sales_by_publisher', 'sales_by_book', 'order_summary'], true))
                <x-field label="Dari" class="w-36">
                    <x-input type="date" name="date_from" value="{{ $filters['date_from'] }}" />
                </x-field>
                <x-field label="Sampai" class="w-36">
                    <x-input type="date" name="date_to" value="{{ $filters['date_to'] }}" />
                </x-field>
            @endif

            <x-button type="submit" variant="secondary">Tampilkan</x-button>
        </x-filter-bar>
    </x-card>

    <x-card>
        <x-card-header :title="collect($types)->firstWhere('value', $type)['label'] ?? 'Laporan'" icon="chart" />

        @if (count($report['rows']) === 0)
            <x-empty-state message="Tidak ada data untuk filter ini." icon="chart" />
        @else
            <x-data-table
                :rows="collect($report['rows'])->map(fn ($row, $index) => ['id' => $index] + $row)->all()"
                :columns="collect($report['columns'])->map(fn ($column) => [
                    'key' => $column['key'],
                    'label' => $column['label'],
                    'type' => $column['type'] ?? 'text',
                    'align' => ($column['type'] ?? 'text') === 'currency' || ($column['type'] ?? '') === 'number' ? 'right' : 'left',
                ])->all()"
            />
        @endif
    </x-card>
@endsection
