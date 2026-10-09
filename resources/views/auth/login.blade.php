@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <x-card class="p-6">
        <h1 class="text-base font-semibold text-ink">Masuk</h1>
        <p class="mt-1 mb-5 text-sm text-ink-soft">Gunakan akun Anda untuk melanjutkan.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-field label="Email" name="email" required>
                <x-input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    autofocus
                    :invalid="$errors->has('email')"
                />
            </x-field>

            <x-field label="Kata Sandi" name="password" required>
                <x-input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    :invalid="$errors->has('password')"
                />
            </x-field>

            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500"
                />
                Ingat saya
            </label>

            <x-button type="submit" class="w-full">Masuk</x-button>
        </form>
    </x-card>
@endsection
