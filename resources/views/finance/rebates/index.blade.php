@extends('layouts.app')

@section('title', 'Rebate')

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        title="Rebate / Cashback"
        subtitle="Pengajuan, persetujuan, dan pembayaran rebate."
    >
        <x-slot:actions>
            @if ($can['request'])
                <x-button x-on:click="$dispatch('open-modal', 'request-rebate')">Ajukan Rebate</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('rebates.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nomor / sekolah" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada rebate."
            empty-icon="percent"
            :rows="$rebates"
            :columns="[
                ['key' => 'number', 'label' => 'No. Rebate'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'invoice', 'label' => 'Invoice'],
                ['key' => 'type', 'label' => 'Tipe'],
                ['key' => 'base_amount', 'label' => 'Dasar', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'amount', 'label' => 'Nilai Rebate', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ['key' => 'requested_at', 'label' => 'Diajukan', 'type' => 'datetime'],
                ['label' => 'Aksi', 'view' => 'finance.rebates.partials.actions', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $rebates->links() }}</div>

    @if ($can['request'])
        <x-modal name="request-rebate" title="Ajukan Rebate">
            <form method="POST" action="{{ route('rebates.store') }}" class="space-y-3">
                @csrf

                <x-field label="Invoice (harus sudah lunas)" name="invoice_id" required hint="Hanya invoice lunas yang dapat diajukan rebate.">
                    <x-select name="invoice_id" :invalid="$errors->has('invoice_id')">
                        <option value="">— Pilih Invoice —</option>
                        @foreach ($eligibleInvoices as $option)
                            <option value="{{ $option['value'] }}" @selected(old('invoice_id') == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Aturan Rebate" name="rebate_rule_id" required>
                    <x-select name="rebate_rule_id" :invalid="$errors->has('rebate_rule_id')">
                        <option value="">— Pilih Aturan —</option>
                        @foreach ($rules as $rule)
                            <option value="{{ $rule['value'] }}" @selected(old('rebate_rule_id') == $rule['value'])>{{ $rule['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Catatan" name="notes">
                    <x-textarea name="notes" rows="2">{{ old('notes') }}</x-textarea>
                </x-field>

                <div class="flex justify-end gap-2 pt-1">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'request-rebate')">Batal</x-button>
                    <x-button type="submit">Ajukan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
