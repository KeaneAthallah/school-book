@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        title="Pembayaran"
        subtitle="Pencatatan dan verifikasi pembayaran sekolah."
        icon="wallet"
    >
        <x-slot:actions>
            @if ($can['manage'])
                <x-button x-on:click="$dispatch('open-modal', 'record-payment')">Catat Pembayaran</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('payments.index')">
            <x-field label="Cari" class="w-60">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nomor / sekolah / referensi" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada pembayaran."
            empty-icon="wallet"
            :rows="$payments"
            :columns="[
                ['key' => 'number', 'label' => 'No. Pembayaran'],
                ['key' => 'invoice', 'label' => 'Invoice', 'link' => 'invoices.show', 'linkKey' => 'invoice_id'],
                ['key' => 'payment_date', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'method', 'label' => 'Metode'],
                ['key' => 'reference', 'label' => 'Referensi'],
                ['key' => 'amount', 'label' => 'Jumlah', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'allocated_amount', 'label' => 'Dialokasikan', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'remaining_amount', 'label' => 'Sisa', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ['label' => 'Aksi', 'view' => 'finance.payments.partials.actions', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $payments->links() }}</div>

    @if ($can['manage'])
        <x-modal name="record-payment" title="Catat Pembayaran" wide>
            <form method="POST" action="{{ route('payments.store') }}" class="space-y-3"
                x-data="{
                    useInvoice: {{ $selectedInvoiceId > 0 ? 'true' : 'false' }},
                    schoolId: '{{ old('school_id', $selectedSchoolId ?: '') }}',
                    amountPlaceholder: '{{ $selectedOutstanding !== null ? 'Sisa: '.Format::idr($selectedOutstanding) : '' }}',
                    syncInvoice(select) {
                        const opt = select.selectedOptions[0];
                        const sid = opt?.dataset?.schoolId;
                        if (sid) {
                            this.schoolId = sid;
                            this.useInvoice = true;
                            this.amountPlaceholder = opt.dataset.outstandingLabel
                                ? 'Sisa: ' + opt.dataset.outstandingLabel
                                : '';
                        } else {
                            this.useInvoice = false;
                            this.amountPlaceholder = '';
                        }
                    },
                }"
                x-init="
                    @if ($selectedInvoiceId > 0)
                        $nextTick(() => $dispatch('open-modal', 'record-payment'));
                    @endif
                "
            >
                @csrf

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Invoice (opsional)" name="allocations.0.invoice_id">
                        <x-select name="allocations[0][invoice_id]" x-on:change="syncInvoice($event.target)">
                            <option value="">— Pilih Invoice (opsional) —</option>
                            @foreach ($openInvoices as $option)
                                <option value="{{ $option['value'] }}" data-school-id="{{ $option['school_id'] }}"
                                    data-outstanding-label="{{ Format::idr($option['outstanding']) }}"
                                    @selected((int) $selectedInvoiceId === (int) $option['value'])>{{ $option['label'] }} — sisa {{ Format::idr($option['outstanding']) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Sekolah" name="school_id" required>
                        <x-select name="school_id" x-model="schoolId" :invalid="$errors->has('school_id')">
                            <option value="">— Pilih —</option>
                            @foreach ($schools as $option)
                                <option value="{{ $option['value'] }}" @selected(old('school_id') == $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Tanggal" name="payment_date" required>
                        <x-input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" :invalid="$errors->has('payment_date')" />
                    </x-field>

                    <x-field label="Jumlah" name="amount" required>
                        <x-input type="number" step="any" name="amount" value="{{ old('amount') }}" x-bind:placeholder="amountPlaceholder" :invalid="$errors->has('amount')" />
                    </x-field>

                    <x-field label="Metode" name="payment_method_id">
                        <x-select name="payment_method_id">
                            <option value="">— Pilih —</option>
                            @foreach ($methods as $option)
                                <option value="{{ $option['value'] }}" @selected(old('payment_method_id') == $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Referensi" name="reference">
                        <x-input name="reference" value="{{ old('reference') }}" />
                    </x-field>

                    <x-field label="Catatan" name="notes" class="sm:col-span-2">
                        <x-textarea name="notes" rows="2">{{ old('notes') }}</x-textarea>
                    </x-field>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'record-payment')">Batal</x-button>
                    <x-button type="submit">Simpan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
