@extends('layouts.app')

@section('title', 'Peran & Izin')

@section('content')
    <x-page-header
        title="Peran & Izin"
        subtitle="Pilih sebuah peran, lalu centang izin yang boleh dilakukannya."
        icon="shield"
    />

    <div
        x-data="{ active: {{ $roles[0]['id'] ?? 0 }} }"
        class="grid items-start gap-4 lg:grid-cols-[17rem_minmax(0,1fr)]"
    >
        <x-card class="lg:sticky lg:top-20">
            <div class="border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold text-ink">Daftar Peran</h2>
                <p class="mt-0.5 text-xs text-ink-soft">Klik peran untuk mengatur izinnya.</p>
            </div>

            <nav class="space-y-0.5 p-2">
                @foreach ($roles as $role)
                    <button
                        type="button"
                        x-on:click="active = {{ $role['id'] }}"
                        :class="active === {{ $role['id'] }}
                            ? 'bg-brand-600 text-white'
                            : 'text-ink-soft hover:bg-canvas'"
                        class="flex w-full items-center justify-between gap-3 rounded-control px-3 py-2 text-left text-sm transition"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{ $role['name'] }}</span>
                            <span class="block truncate text-[11px] opacity-75">
                                {{ count($role['permission_ids']) }} izin · {{ $role['users_count'] }} pengguna
                            </span>
                        </span>
                        <x-icon name="chevronRight" class="h-4 w-4 shrink-0" />
                    </button>
                @endforeach
            </nav>
        </x-card>

        <div class="grid gap-4">
            @foreach ($roles as $role)
                <div x-show="active === {{ $role['id'] }}" x-cloak>
                    <x-card>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3.5">
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-ink">{{ $role['name'] }}</h2>
                                <p class="mt-0.5 text-xs text-ink-soft">{{ $role['description'] ?: 'Tanpa deskripsi' }}</p>
                            </div>

                            @if ($role['locked'])
                                <x-status-badge value="active" label="Peran sistem" :dot="false" />
                            @endif
                        </div>

                        @if ($role['locked'])
                            <div class="space-y-3 p-4">
                                <x-alert tone="info">
                                    Peran Administrator selalu memiliki seluruh izin dan tidak dapat diubah.
                                </x-alert>

                                @include('settings.partials.permission-matrix', [
                                    'permissionGroups' => $permissionGroups,
                                    'selectedIds' => $role['permission_ids'],
                                    'disabled' => true,
                                ])
                            </div>
                        @else
                            <form
                                x-data="permissionPicker(@js($role['permission_ids']))"
                                method="POST"
                                action="{{ route('roles.update', $role['id']) }}"
                            >
                                @csrf
                                @method('PUT')

                                <div class="space-y-4 p-4">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <x-field label="Nama Peran" name="name" required>
                                            <x-input name="name" value="{{ $role['name'] }}" :invalid="$errors->has('name')" />
                                        </x-field>
                                        <x-field label="Deskripsi" name="description">
                                            <x-input name="description" value="{{ $role['description'] }}" />
                                        </x-field>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-card bg-canvas px-3 py-2">
                                        <p class="text-xs text-ink-soft">
                                            <span class="font-semibold tabular-nums text-ink" x-text="selected.length"></span>
                                            dari {{ $permissionCount }} izin dipilih
                                        </p>
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400"
                                            x-on:click="clear()"
                                        >Kosongkan semua</button>
                                    </div>

                                    @include('settings.partials.permission-matrix', [
                                        'permissionGroups' => $permissionGroups,
                                        'selectedIds' => $role['permission_ids'],
                                        'disabled' => false,
                                    ])
                                </div>

                                <div class="flex justify-end border-t border-line px-4 py-3">
                                    <x-button type="submit">Simpan Peran</x-button>
                                </div>
                            </form>
                        @endif
                    </x-card>
                </div>
            @endforeach
        </div>
    </div>
@endsection
