@extends('layouts.app')

@section('title', 'Akses Terbatas')

@section('content')
    <x-page-header
        title="Akses Terbatas"
        subtitle="Akun Anda belum memiliki peran dashboard yang dikenali."
    />

    <x-alert tone="warning">
        Akun ini belum ditautkan ke salah satu peran BOOKFLOW (Admin, Operator Pesanan, Gudang, Keuangan,
        Sales / PIC, atau Pimpinan), sehingga tidak ada ringkasan operasional yang dapat ditampilkan.
        Hubungi administrator untuk menetapkan peran yang sesuai.
    </x-alert>

    <x-card>
        <x-card-header
            title="Izin yang Dimiliki"
            icon="shield"
            subtitle="Hak akses yang saat ini melekat pada akun Anda"
        />

        @if (count($permissions) === 0)
            <x-empty-state message="Akun ini belum memiliki izin apa pun." icon="shield" />
        @else
            <ul class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($permissions as $permission)
                    <li class="flex items-center gap-2 text-sm text-ink-soft">
                        <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span class="min-w-0 truncate">{{ $permission }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
@endsection
