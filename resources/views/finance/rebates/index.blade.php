@extends('layouts.app')

@section('title', 'Rebate / Cashback')

@section('content')
    <x-page-header
        title="Rebate / Cashback"
        subtitle="Rekap rebate otomatis dari nota pesanan per sekolah."
        icon="percent"
    />

    <x-card>
        <x-filter-bar method="get" :action="route('rebates.index')">
            <x-field label="Dari" class="w-40">
                <x-input type="date" name="date_from" value="{{ $filters['date_from'] }}" />
            </x-field>
            <x-field label="Sampai" class="w-40">
                <x-input type="date" name="date_to" value="{{ $filters['date_to'] }}" />
            </x-field>
            <x-field label="Sekolah" class="w-56">
                <x-select name="school_id">
                    <option value="">— Semua —</option>
                    @foreach ($schools as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $filters['school_id'] === (string) $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        @forelse ($groups as $group)
            <div class="mb-4 rounded-control border border-line">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-panel-soft px-4 py-3">
                    <div>
                        <p class="font-medium text-ink">{{ $group['school'] }}</p>
                        <p class="text-xs text-ink-soft">Rebate {{ $group['rebate_percent'] }}% · {{ $group['order_count'] }} nota</p>
                    </div>
                    <div class="text-right text-sm">
                        <p>Subtotal: <span class="font-medium">{{ number_format((float) $group['total_subtotal'], 2, ',', '.') }}</span></p>
                        <p>Nilai Rebate: <span class="font-medium text-emerald-600">{{ number_format((float) $group['total_discount'], 2, ',', '.') }}</span></p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs text-ink-soft">
                                <th class="px-4 py-2">No. Nota</th>
                                <th class="px-4 py-2">Tanggal</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                                <th class="px-4 py-2 text-right">Diskon %</th>
                                <th class="px-4 py-2 text-right">Nilai Diskon</th>
                                <th class="px-4 py-2">Faktur Terkait</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['orders'] as $order)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-4 py-2">{{ $order['number'] }}</td>
                                    <td class="px-4 py-2">{{ $order['date'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $order['subtotal'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">{{ $order['discount_percent'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $order['discount_amount'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2">
                                        @foreach ($order['invoices'] as $inv)
                                            <a href="{{ route('invoices.show', $inv['id']) }}" class="mr-1 inline-block rounded bg-panel-soft px-2 py-0.5 text-xs text-brand-600 hover:underline dark:text-brand-300">{{ $inv['number'] }} ({{ $inv['status'] }})</a>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2">{{ $order['status'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-ink-soft">Belum ada rebate.</p>
        @endforelse
    </x-card>
@endsection