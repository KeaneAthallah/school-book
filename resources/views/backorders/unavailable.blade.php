@extends('layouts.app')
@section('title', 'Rekap Item Tidak Tersedia')
@section('content')
    <x-page-header title="Rekap Item Tidak Tersedia" subtitle="Item pesanan yang ditandai kosong, dikelompokkan per barang." icon="boxes" />
    @if (empty($groups))
        <x-card>
            <x-empty-state message="Belum ada item yang ditandai tidak tersedia." icon="checkCircle" />
        </x-card>
    @else
        @foreach ($groups as $group)
            <x-card class="mb-4">
                <x-card-header
                    title="{{ $group['product'] }}"
                    subtitle="{{ $group['code'] ? 'Kode: '.$group['code'] : '' }}"
                    icon="boxes"
                >
                    <x-slot:action>
                        <span class="text-sm font-medium text-ink-soft">Total {{ $group['total_qty'] }}</span>
                    </x-slot:action>
                </x-card-header>
                <x-data-table
                    empty="Tidak ada detail."
                    :rows="$group['details']"
                    :columns="[
                        ['key' => 'order_number', 'label' => 'No. Pesanan'],
                        ['key' => 'school', 'label' => 'Sekolah'],
                        ['key' => 'date', 'label' => 'Tanggal'],
                        ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ]"
                />
            </x-card>
        @endforeach
    @endif
@endsection