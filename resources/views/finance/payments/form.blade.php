@extends('layouts.app')

@section('title', 'Ubah Pembayaran '.$payment['number'])

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        :title="'Ubah Pembayaran '.$payment['number']"
        subtitle="Hanya pembayaran berstatus Draft yang dapat diubah."
        icon="wallet"
    >
        <x-slot:actions>
            <x-button :href="route('payments.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form
        method="POST"
        action="{{ route('payments.update', $payment['id']) }}"
        class="space-y-4"
        x-data="{
            schoolId: '{{ old('school_id', $payment['school_id']) }}',
            amountPlaceholder: '{{ $selectedOutstanding !== null ? 'Sisa: '.Format::idr($selectedOutstanding) : '' }}',
            syncInvoice(select) {
                const opt = select.selectedOptions[0];
                const sid = opt?.dataset?.schoolId;
                if (sid) {
                    this.schoolId = sid;
                    this.amountPlaceholder = opt.dataset.outstandingLabel
                        ? 'Sisa: ' + opt.dataset.outstandingLabel
                        : '';
                } else {
                    this.amountPlaceholder = '';
                }
            },
        }"
    >
        @csrf
        @method('PUT')

        <x-card>
            <x-card-header title="Informasi Pembayaran" icon="wallet" />

            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Invoice (opsional)" name="allocations.0.invoice_id">
                    <x-select name="allocations[0][invoice_id]" x-on:change="syncInvoice($event.target)">
                        <option value="">— Pilih Invoice (opsional) —</option>
                        @foreach ($openInvoices as $option)
                            <option value="{{ $option['value'] }}" data-school-id="{{ $option['school_id'] }}"
                                data-outstanding-label="{{ Format::idr($option['outstanding']) }}"
                                @selected((int) old('allocations.0.invoice_id', $payment['invoice_id']) === (int) $option['value'])>{{ $option['label'] }} — sisa {{ Format::idr($option['outstanding']) }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Sekolah" name="school_id" required>
                    <x-select name="school_id" x-model="schoolId" :invalid="$errors->has('school_id')">
                        <option value="">— Pilih —</option>
                        @foreach ($schools as $option)
                            <option value="{{ $option['value'] }}" @selected((string) old('school_id', $payment['school_id']) === (string) $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Tanggal" name="payment_date" required>
                    <x-input type="date" name="payment_date" value="{{ old('payment_date', $payment['payment_date']) }}" :invalid="$errors->has('payment_date')" />
                </x-field>

                <x-field label="Jumlah" name="amount" required>
                    <x-input type="number" step="any" name="amount" value="{{ old('amount', $payment['amount']) }}" x-bind:placeholder="amountPlaceholder" :invalid="$errors->has('amount')" />
                </x-field>

                <x-field label="Metode" name="payment_method_id">
                    <x-select name="payment_method_id">
                        <option value="">— Pilih —</option>
                        @foreach ($methods as $option)
                            <option value="{{ $option['value'] }}" @selected((string) old('payment_method_id', $payment['payment_method_id']) === (string) $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Referensi" name="reference">
                    <x-input name="reference" value="{{ old('reference', $payment['reference']) }}" />
                </x-field>

                <x-field label="Catatan" name="notes" class="sm:col-span-2">
                    <x-textarea name="notes" rows="2">{{ old('notes', $payment['notes']) }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                <p class="text-sm text-ink-soft">
                    Alokasi invoice akan diperbarui mengikuti pilihan di atas. Kosongkan bila pembayaran belum dialokasikan.
                </p>
                <div class="flex gap-2">
                    <x-button :href="route('payments.index')" variant="secondary">Batal</x-button>
                    <x-button type="submit">Simpan Perubahan</x-button>
                </div>
            </div>
        </x-card>
    </form>
@endsection
