@extends('layouts.app')

@section('title', $role->title())

@section('content')
    @php
        use App\Support\Chart;
    @endphp

    <x-page-header :title="$role->title()" :subtitle="$role->subtitle()">
        <x-slot:actions>
            @include('dashboard.partials.filter', ['period' => $period])
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-4 text-xs text-ink-soft">
        Periode {{ $period->label() }} · data dibatasi pada sekolah yang ditugaskan kepada Anda
    </p>

    <x-dashboard.kpi-grid :kpis="$data['kpis']" />

    @include('dashboard.partials.quick-actions', ['actions' => [
        ['label' => 'Sekolah Saya', 'icon' => 'school', 'route' => 'master.index', 'params' => ['resource' => 'schools'], 'permission' => 'schools.view'],
        ['label' => 'Pesanan Sekolah', 'icon' => 'cart', 'route' => 'orders.index', 'permission' => 'orders.view'],
        ['label' => 'Status Pemenuhan', 'icon' => 'truck', 'route' => 'shipments.index', 'permission' => 'shipments.view'],
        ['label' => 'Piutang', 'icon' => 'fileText', 'route' => 'receivables.index', 'permission' => 'invoices.view'],
        ['label' => 'Laporan Penjualan', 'icon' => 'chart', 'route' => 'reports.index', 'permission' => 'reports.view'],
    ]])

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-card-header
                title="Penjualan per Sekolah"
                icon="chart"
                :subtitle="'Nilai barang terkirim · '.$period->label()"
            />
            @if (count($data['sales_by_school']) === 0)
                <x-empty-state message="Tidak ada penjualan pada periode ini." icon="chart" />
            @else
                <div class="p-4">
                    <x-chart
                        :config="Chart::horizontalBars($data['sales_by_school'])"
                        :height="max(170, count($data['sales_by_school']) * 46).'px'"
                    />
                </div>
            @endif
        </x-card>

        <x-card>
            <x-card-header title="Sekolah Ditugaskan" icon="school" :subtitle="'Hanya sekolah dengan PIC Anda'">
                <x-slot:action>
                    <a href="{{ route('master.index', ['resource' => 'schools']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.school-table', ['rows' => $data['assigned_schools']])
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header
            title="Progres Pemenuhan"
            icon="bolt"
            subtitle="Tahapan dihitung dari entitas berbeda, hanya untuk sekolah Anda"
        />

        @include('dashboard.partials.pipeline', ['steps' => $data['pipeline']])
    </x-card>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Pesanan Terbaru" icon="clipboard" subtitle="Dari sekolah yang ditugaskan">
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['recent_orders']])
        </x-card>

        <x-card>
            <x-card-header title="Perlu Tindak Lanjut" icon="clock" subtitle="Belum ada pesanan dalam 45 hari terakhir">
                <x-slot:action>
                    <a href="{{ route('orders.create') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Buat pesanan</a>
                </x-slot:action>
            </x-card-header>

            @if (count($data['stale_schools']) === 0)
                <x-empty-state message="Semua sekolah Anda aktif memesan." icon="checkCircle" />
            @else
                <x-data-table
                    empty="Semua sekolah Anda aktif memesan."
                    empty-icon="checkCircle"
                    :rows="$data['stale_schools']"
                    :columns="[
                        ['key' => 'name', 'label' => 'Sekolah'],
                        ['key' => 'district', 'label' => 'Kecamatan'],
                        ['label' => 'Terakhir Pesan', 'view' => 'dashboard.partials.last-order'],
                    ]"
                />
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-card>
            <x-card-header title="Siap Diproses" icon="clipboard" subtitle="Stok sudah dialokasikan untuk pesanan sekolah Anda">
                <x-slot:action>
                    <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Lihat semua</a>
                </x-slot:action>
            </x-card-header>

            @include('dashboard.partials.order-table', ['rows' => $data['ready_queue'], 'empty' => 'Belum ada pesanan siap diproses.'])
        </x-card>

        <x-card>
            <x-card-header title="Peringatan" icon="bell" :subtitle="count($data['notifications']).' hal terkait sekolah Anda'" />

            @include('dashboard.partials.alerts', ['alerts' => $data['notifications']])
        </x-card>
    </div>
@endsection
