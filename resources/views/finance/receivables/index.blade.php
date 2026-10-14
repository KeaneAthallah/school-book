@extends('layouts.app')

@section('title', 'Piutang')

@section('content')
    @php
        use App\Support\Format;

        $agingLabels = [
            'current' => 'Belum jatuh tempo',
            '1_30' => '1-30 hari',
            '31_60' => '31-60 hari',
            'over_60' => 'Lebih dari 60 hari',
        ];
    @endphp

    <x-page-header
        title="Piutang"
        subtitle="Sisa tagihan invoice yang belum dibayar penuh, termasuk yang sudah jatuh tempo."
        icon="wallet"
    />

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Ditagihkan" :value="Format::idr($summary['total'])" icon="receipt" chip-tone="cyan" />
        <x-stat-card label="Sudah Dibayar" :value="Format::idr($summary['paid'])" icon="wallet" chip-tone="emerald" tone="text-emerald-700 dark:text-emerald-400" />
        <x-stat-card label="Sisa Piutang" :value="Format::idr($summary['outstanding'])" icon="fileText" chip-tone="amber" tone="text-amber-600 dark:text-amber-400" />
        <x-stat-card label="Jatuh Tempo" :value="Format::idr($summary['overdue_amount'])" icon="alert" chip-tone="rose" tone="text-rose-600 dark:text-rose-400" :hint="Format::number($summary['overdue_count']).' invoice'" />
    </div>

    <x-card class="mt-4">
        <x-card-header title="Umur Piutang" icon="clock" subtitle="Berdasarkan selisih tanggal jatuh tempo" />
        <ul class="grid grid-cols-1 divide-line min-[420px]:grid-cols-2 lg:grid-cols-4 lg:divide-x">
            @foreach ($aging as $key => $value)
                <li class="px-4 py-3">
                    <p class="text-xs text-ink-soft">{{ $agingLabels[$key] ?? $key }}</p>
                    <p class="mt-1 text-lg font-semibold text-ink tabular-nums">{{ Format::idr($value) }}</p>
                </li>
            @endforeach
        </ul>
    </x-card>

    <x-card class="mt-4">
        <x-filter-bar method="get" :action="route('receivables.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="No. invoice / sekolah" />
            </x-field>
            <x-field label="Status" class="w-44">
                <x-select name="bucket">
                    <option value="">Semua</option>
                    <option value="overdue" @selected($filters['bucket'] === 'overdue')>Jatuh tempo</option>
                    <option value="partial" @selected($filters['bucket'] === 'partial')>Sebagian dibayar</option>
                    <option value="unpaid" @selected($filters['bucket'] === 'unpaid')>Belum dibayar</option>
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Tidak ada piutang pada filter ini."
            empty-icon="checkCircle"
            :rows="$invoices"
            :columns="[
                ['key' => 'number', 'label' => 'No. Invoice', 'link' => 'invoices.show'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'invoice_date', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'paid', 'label' => 'Dibayar', 'type' => 'currency', 'align' => 'right'],
                ['label' => 'Sisa', 'view' => 'finance.receivables.partials.outstanding', 'align' => 'right'],
                ['label' => 'Status', 'view' => 'finance.receivables.partials.state'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $invoices->links() }}</div>
@endsection
