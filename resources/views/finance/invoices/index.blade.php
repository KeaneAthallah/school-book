@extends('layouts.app')

@section('title', 'Invoice')

@section('content')
    <x-page-header
        title="Invoice"
        subtitle="Tagihan yang diterbitkan dari kuantitas yang dikirim."
    />

    <x-card>
        <x-filter-bar method="get" :action="route('invoices.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="No. invoice / sekolah" />
            </x-field>
            <x-field label="Status Bayar" class="w-44">
                <x-select name="payment_status">
                    <option value="">Semua status</option>
                    @foreach ($paymentStatuses as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['payment_status'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada invoice."
            empty-icon="receipt"
            :rows="$invoices"
            :columns="[
                ['key' => 'number', 'label' => 'No. Invoice', 'link' => 'invoices.show'],
                ['key' => 'invoice_date', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'total_amount', 'label' => 'Total', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'paid_amount', 'label' => 'Dibayar', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'outstanding_amount', 'label' => 'Sisa', 'type' => 'currency', 'align' => 'right'],
                ['key' => 'payment_status', 'label' => 'Status', 'view' => 'finance.invoices.partials.payment-state'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $invoices->links() }}</div>
@endsection
