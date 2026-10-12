@extends('layouts.app')

@section('title', 'Profil Perusahaan')

@section('content')
    @php
        $value = fn (string $key): string => old($key, (string) ($values[$key] ?? ''));
    @endphp

    <x-page-header title="Profil Perusahaan" subtitle="Identitas yang tercetak pada invoice dan surat jalan">
        <x-slot:actions>
            <x-button :href="route('dashboard')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <x-card>
            <x-card-header title="Identitas Perusahaan" icon="fileText" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Nama Perusahaan" name="company_name" required class="sm:col-span-2">
                    <x-input type="text" name="company_name" :value="$value('company_name')" :invalid="$errors->has('company_name')" />
                </x-field>

                <x-field label="Alamat" name="company_address" class="sm:col-span-2">
                    <x-textarea name="company_address" rows="3" :invalid="$errors->has('company_address')">{{ $value('company_address') }}</x-textarea>
                </x-field>

                <x-field label="Telepon" name="company_phone">
                    <x-input type="text" name="company_phone" :value="$value('company_phone')" :invalid="$errors->has('company_phone')" />
                </x-field>

                <x-field label="Email" name="company_email">
                    <x-input type="email" name="company_email" :value="$value('company_email')" :invalid="$errors->has('company_email')" />
                </x-field>
            </div>
        </x-card>

        <x-card>
            <x-card-header title="Keuangan" icon="percent" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Tarif Pajak (%)" name="tax_rate" hint="Diterapkan saat menerbitkan invoice">
                    <x-input type="number" name="tax_rate" step="any" :value="$value('tax_rate')" :invalid="$errors->has('tax_rate')" />
                </x-field>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>
@endsection
