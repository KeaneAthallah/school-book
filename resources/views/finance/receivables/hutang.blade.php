@extends('layouts.app')

@section('title', 'Barang Belum Dikirim')

@section('content')
    <x-page-header
        title="Barang Belum Dikirim"
        subtitle="Invoice yang sudah lunas tetapi pesanannya belum terkirim penuh."
        icon="truck"
    />

    <x-card>
        <x-data-table
            empty="Tidak ada invoice lunas yang belum dikirim."
            empty-icon="checkCircle"
            :rows="$invoices"
            :columns="[
                ['key' => 'number', 'label' => 'No. Invoice', 'link' => 'invoices.show'],
                ['key' => 'order_number', 'label' => 'No. Pesanan'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'paid_date', 'label' => 'Tgl Bayar', 'type' => 'date'],
                ['key' => 'due_date', 'label' => 'Tgl Janji Kirim', 'type' => 'date'],
                ['key' => 'total_amount', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
            ]"
        />
    </x-card>
@endsection