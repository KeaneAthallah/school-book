@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <x-card class="p-6 sm:p-7">
        <div class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-ink">Masuk ke akun Anda</h1>
            <p class="mt-1.5 text-sm text-ink-soft">Gunakan akun Anda untuk melanjutkan.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-field label="Email" name="email" required>
                <div class="relative">
                    <x-input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        autofocus
                        :invalid="$errors->has('email')"
                        class="pl-10"
                    />
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                        <x-icon name="mail" class="h-4 w-4" />
                    </span>
                </div>
            </x-field>

            <x-field label="Kata Sandi" name="password" required>
                <div x-data="{ show: false }" class="relative">
                    <x-input
                        type="password"
                        name="password"
                        x-bind:type="show ? 'text' : 'password'"
                        autocomplete="current-password"
                        :invalid="$errors->has('password')"
                        class="pe-11 pl-10"
                    />
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                        <x-icon name="lock" class="h-4 w-4" />
                    </span>
                    <button
                        type="button"
                        x-on:click.prevent="show = ! show"
                        :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted transition hover:text-ink"
                    >
                        <template x-if="show"><x-icon name="eyeOff" class="h-4 w-4" /></template>
                        <template x-if="! show"><x-icon name="eye" class="h-4 w-4" /></template>
                    </button>
                </div>
            </x-field>

            <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-soft">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="h-4 w-4 rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500 focus:ring-offset-0"
                />
                Ingat saya
            </label>

            <x-button type="submit" class="mt-1 w-full">
                Masuk
                <x-icon name="arrowRight" class="h-4 w-4" />
            </x-button>
        </form>

        <p class="mt-6 flex items-center justify-center gap-1.5 border-t border-line pt-5 text-center text-xs text-ink-soft">
            <x-icon name="shield" class="h-3.5 w-3.5" />
            Sistem manajemen operasional &amp; keuangan CV. Prana Media
        </p>
    </x-card>
@endsection
