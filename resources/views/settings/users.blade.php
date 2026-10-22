@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <x-page-header title="Pengguna" subtitle="Akun pengguna dan peran yang dimilikinya." icon="users">
        <x-slot:actions>
            @if ($can['manage'])
                <x-button x-on:click="$dispatch('open-modal', 'create-user')">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Pengguna
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('users.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama / email" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada pengguna."
            empty-icon="users"
            :rows="$users"
            :columns="[
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'email', 'label' => 'Email'],
                ['label' => 'Peran', 'view' => 'settings.partials.user-roles'],
                ['label' => 'Aksi', 'view' => 'settings.partials.user-actions', 'align' => 'right'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $users->links() }}</div>

    @if ($can['manage'])
        <x-modal name="create-user" title="Tambah Pengguna" x-init="@if ($errors->any() && old('_method') !== 'PUT') $dispatch('open-modal', 'create-user') @endif">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                @csrf

                <x-field label="Nama" name="name" required>
                    <x-input name="name" value="{{ old('name') }}" :invalid="$errors->has('name')" />
                </x-field>

                <x-field label="Email" name="email" required>
                    <x-input type="email" name="email" value="{{ old('email') }}" :invalid="$errors->has('email')" />
                </x-field>

                <x-field label="Kata Sandi" name="password" required>
                    <x-input type="password" name="password" :invalid="$errors->has('password')" />
                </x-field>

                <fieldset>
                    <legend class="text-xs font-medium text-ink-soft">Peran</legend>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 text-sm text-ink-soft">
                                <input type="checkbox" name="roles[]" value="{{ $role['value'] }}" @checked(in_array($role['value'], old('roles', []))) class="rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500" />
                                {{ $role['label'] }}
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex justify-end gap-2 pt-1">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'create-user')">Batal</x-button>
                    <x-button type="submit">Simpan</x-button>
                </div>
            </form>
        </x-modal>

        @foreach ($users as $user)
            <x-modal name="edit-user-{{ $user['id'] }}" title="Ubah Pengguna">
                <form method="POST" action="{{ route('users.update', $user['id']) }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <x-field label="Nama" name="name" required>
                        <x-input name="name" value="{{ $user['name'] }}" :invalid="$errors->has('name')" />
                    </x-field>

                    <x-field label="Email" name="email" required>
                        <x-input type="email" name="email" value="{{ $user['email'] }}" :invalid="$errors->has('email')" />
                    </x-field>

                    <x-field label="Kata Sandi" name="password" hint="Kosongkan bila tidak diubah.">
                        <x-input type="password" name="password" :invalid="$errors->has('password')" />
                    </x-field>

                    <fieldset>
                        <legend class="text-xs font-medium text-ink-soft">Peran</legend>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 text-sm text-ink-soft">
                                    <input type="checkbox" name="roles[]" value="{{ $role['value'] }}" @checked(in_array($role['value'], $user['role_ids'])) class="rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500" />
                                    {{ $role['label'] }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-1">
                        <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'edit-user-{{ $user['id'] }}')">Batal</x-button>
                        <x-button type="submit">Simpan</x-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endif
@endsection
