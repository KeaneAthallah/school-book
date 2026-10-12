@extends('layouts.app')

@section('title', 'Picking '.$picking['number'])

@section('content')
    <x-page-header
        :title="'Picking '.$picking['number']"
        :subtitle="($picking['order'] ?? '').' · '.($picking['school'] ?? '')"
    >
        <x-slot:actions>
            <x-button :href="route('pickings.index')" variant="secondary">Kembali</x-button>

            @if ($can['pack'])
                <form method="POST" action="{{ route('pickings.pack', $picking['id']) }}" x-data x-on:submit="if (! confirm('Konfirmasi packing selesai?')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit">Selesai Packing</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-card-header title="Item" icon="clipboard">
            <x-slot:action>
                <x-status-badge :value="$picking['status']" :label="$picking['status_label']" />
            </x-slot:action>
        </x-card-header>

        @if ($can['pick'])
            <form method="POST" action="{{ route('pickings.record', $picking['id']) }}">
                @csrf
                <div class="scroll-slim overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-line bg-panel-soft text-left text-xs font-semibold tracking-wide text-ink-soft uppercase">
                                <th scope="col" class="px-3 py-2.5">Barang</th>
                                <th scope="col" class="px-3 py-2.5 text-right">Diminta</th>
                                <th scope="col" class="px-3 py-2.5 text-right">Diambil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($picking['items'] as $index => $item)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-3 py-2.5">{{ $item['code'] }} {{ $item['product'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $item['quantity'] }}</td>
                                    <td class="w-32 px-3 py-2.5">
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}" />
                                        <x-input
                                            type="number"
                                            name="items[{{ $index }}][picked_quantity]"
                                            min="0"
                                            max="{{ $item['quantity'] }}"
                                            value="{{ $item['picked_quantity'] ?: $item['quantity'] }}"
                                            class="text-right"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-line px-4 py-3">
                    <x-button type="submit">Simpan Hasil Picking</x-button>
                </div>
            </form>
        @else
            <x-data-table
                empty="Picking ini belum memiliki item."
                empty-icon="boxes"
                :rows="collect($picking['items'])->map(fn ($item, $index) => ['id' => $index] + $item)->all()"
                :columns="[
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'product', 'label' => 'Barang'],
                    ['key' => 'quantity', 'label' => 'Diminta', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'picked_quantity', 'label' => 'Diambil', 'align' => 'right', 'class' => 'tabular-nums'],
                ]"
            />
        @endif
    </x-card>
@endsection
