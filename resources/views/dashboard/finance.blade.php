@extends('layouts.app')

@section('title', $role->title())

@section('content')
    @php
        use App\Support\Chart;
    @endphp

    <x-page-header :title="$role->title()" :subtitle="$role->subtitle()" icon="dashboard">
        <x-slot:actions>
            @include('dashboard.partials.filter', ['period' => $period])
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-4 text-xs text-ink-soft">
        Periode {{ $period->label() }} · piutang dan jatuh tempo adalah kondisi saat ini, bukan angka periode
    </p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Daftar Invoice', 'icon' => 'receipt', 'route' => 'invoices.index', 'permission' => 'invoices.view'],
        ['label' => 'Catat Pembayaran', 'icon' => 'wallet', 'route' => 'payments.index', 'permission' => 'payments.manage'],
        ['label' => 'Verifikasi Pembayaran', 'icon' => 'checkCircle', 'route' => 'payments.index', 'permission' => 'payments.verify'],
        ['label' => 'Piutang', 'icon' => 'fileText', 'route' => 'receivables.index', 'permission' => 'invoices.view'],
        ['label' => 'Piutang Jatuh Tempo', 'icon' => 'alert', 'route' => 'receivables.index', 'params' => ['bucket' => 'overdue'], 'permission' => 'invoices.view'],
        ['label' => 'Rebate / Cashback', 'icon' => 'percent', 'route' => 'rebates.index', 'permission' => 'rebates.view'],
        ['label' => 'Laporan', 'icon' => 'chart', 'route' => 'reports.index', 'permission' => 'reports.view'],
        ['label' => 'Ekspor Laporan', 'icon' => 'fileText', 'route' => 'reports.export', 'permission' => 'reports.view'],
    ]])

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header
                title="Invoice & Pembayaran"
                icon="chart"
                :subtitle="'Nilai invoice dibanding pembayaran terverifikasi · per '.$data['sales_series']['granularity']"
            />
            @if (count($data['sales_series']['labels']) === 0)
                <x-empty-state message="Tidak ada data pada periode ini." icon="chart" />
            @else
                <div class="p-4">
                    <x-chart :config="Chart::salesSeries($data['sales_series'])" height="15rem" />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Status Pembayaran Invoice" icon="receipt" subtitle="Saling eksklusif, tidak dihitung ganda" />
            <div class="p-4">
                @if (collect($data['invoice_status'])->sum('value') === 0)
                    <x-empty-state message="Belum ada invoice." icon="receipt" />
                @else
                    <x-chart :config="Chart::doughnut($data['invoice_status'])" height="15rem" />
                @endif
            </div>
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card>
            <x-card-header title="Umur Piutang" icon="clock" />
            @include('dashboard.partials.aging-table', ['rows' => $data['aging']])
        </x-card>

        <x-card class="xl:col-span-2">
            <x-card-header title="Piutang Jatuh Tempo" icon="alert" :subtitle="$data['buckets']['overdue'].' invoice melewati jatuh tempo'">
                <x-slot:action>
                    <a href="{{ route('receivables.index', ['bucket' => 'overdue']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.overdue-invoice-table', ['rows' => $data['overdue_invoices']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Jatuh Tempo Terdekat" icon="calendar">
                <x-slot:action>
                    <a href="{{ route('invoices.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat invoice</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.upcoming-invoice-table', ['rows' => $data['upcoming_invoices']])
        </x-card>

        <x-card>
            <x-card-header title="Pembayaran Terbaru" icon="wallet" :subtitle="$data['payments_to_verify'].' pembayaran menunggu verifikasi'">
                <x-slot:action>
                    <a href="{{ route('payments.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.payment-table', ['rows' => $data['recent_payments']])
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header
                title="Rebate & Cashback"
                icon="percent"
                :subtitle="$data['rebate_counts']['count'].' nota · '.number_format((float) $data['rebate_counts']['amount'], 0, ',', '.').' nilai rebate'"
            >
                <x-slot:action>
                    <a href="{{ route('rebates.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.rebate-table', ['rows' => $data['rebates']])
        </x-card>

        <x-card>
            <x-card-header title="Peringatan Keuangan" icon="bell" :subtitle="count($data['notifications']).' hal yang perlu ditangani'" />

            @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
        </x-card>
    </div>
@endsection
