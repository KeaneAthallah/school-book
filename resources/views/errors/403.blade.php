@extends('layouts.guest')

@section('title', 'Akses Ditolak')

@section('content')
    <div class="text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-500/15">
            <svg class="h-8 w-8 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Akses Ditolak</h1>
        <p class="mt-3 text-sm text-ink-soft">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}
        </p>
        <div class="mt-6 flex flex-col items-center gap-3">
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-control bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                        Keluar / Ganti Akun
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-control bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Ke Halaman Login
                </a>
            @endauth
        </div>
    </div>
@endsection