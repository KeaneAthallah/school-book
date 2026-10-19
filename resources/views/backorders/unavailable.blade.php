@extends('layouts.app')
@section('title', 'Rekap Item Tidak Tersedia')
@section('content')
    <x-page-header title="Rekap Item Tidak Tersedia" subtitle="Item pesanan yang ditandai kosong." icon="boxes" />
    @foreach ($groups as $group)
        <x-card class="mb-3">
            <div class="px-4 py-3 font-medium">{{ $group['product'] }} ({{ $group['code'] }}) — Total {{ $group['total_qty'] }}</div>
            <table class="w-full text-sm">
                <thead><tr><th class="px-4 py-2">No. Nota</th><th class="px-4 py-2">Sekolah</th><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Qty</th><th class="px-4 py-2">Status</th></tr></thead>
                <tbody>
                @foreach ($group['details'] as $d)
                    <tr><td class="px-4 py-2">{{ $d['order_number'] }}</td><td class="px-4 py-2">{{ $d['school'] }}</td><td class="px-4 py-2">{{ $d['date'] }}</td><td class="px-4 py-2">{{ $d['quantity'] }}</td><td class="px-4 py-2">{{ $d['status'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </x-card>
    @endforeach
@endsection