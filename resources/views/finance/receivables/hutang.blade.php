@extends('layouts.app')

@section('title', 'Barang Belum Dikirim')

@section('content')
    <x-page-header
        title="Barang Belum Dikirim"
        subtitle="Invoice lunas yang masih memiliki sisa barang belum dikirim."
        icon="truck"
    />

    <x-card>
        <x-data-table
            empty="Tidak ada invoice lunas yang barangnya belum dikirim."
            empty-icon="checkCircle"
            :rows="$invoices"
            :columns="[
                ['key' => 'number', 'label' => 'No. Invoice', 'link' => 'invoices.show'],
                ['key' => 'order_number', 'label' => 'No. Pesanan'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'paid_date', 'label' => 'Tgl Bayar', 'type' => 'date'],
                ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                ['key' => 'remaining_quantity', 'label' => 'Sisa Belum Kirim', 'type' => 'number', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'total_amount', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
            ]"
        />
    </x-card>
@endsection