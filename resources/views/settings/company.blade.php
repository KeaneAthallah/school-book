@extends('layouts.app')

@section('title', 'Profil Perusahaan')

@section('content')
    @php
        $value = fn (string $key): string => old($key, (string) ($values[$key] ?? ''));
    @endphp

    <x-page-header title="Profil Perusahaan" subtitle="Identitas yang tercetak pada invoice dan surat jalan" icon="settings">
        <x-slot:actions>
            <x-button :href="route('dashboard')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <x-card>
            <x-card-header title="Identitas Perusahaan" icon="fileText" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Nama Perusahaan" name="company_name" required class="sm:col-span-2">
                    <x-input type="text" name="company_name" :value="$value('company_name')" :invalid="$errors->has('company_name')" />
                </x-field>

                <x-field label="Logo" name="company_logo" hint="JPEG, PNG, atau WebP · maksimal 5 MB" class="sm:col-span-2">
                    <div x-data="photoPreview" class="space-y-1.5">
                        @if (! empty($values['company_logo'] ?? ''))
                            <span class="block">
                                <img src="{{ Storage::url((string) ($values['company_logo'] ?? '')) }}" alt="Logo perusahaan" loading="lazy" class="h-16 w-16 rounded-control border border-line object-cover" />
                            </span>
                        @endif
                        <input
                            type="file"
                            name="company_logo"
                            accept="image/jpeg,image/png,image/webp"
                            aria-label="Logo perusahaan"
                            class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                            @change="pick($event)"
                        />
                        <img x-show="preview" :src="preview" alt="Pratinjau logo perusahaan" class="h-20 w-20 rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                    </div>
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
            <x-card-header title="Header Cetak" subtitle="Identitas di bagian atas invoice dan surat jalan" icon="printer" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Sumber Header" name="header_publisher_id" hint="Profil perusahaan, atau salah satu penerbit">
                    <x-select name="header_publisher_id" :invalid="$errors->has('header_publisher_id')">
                        <option value="">— Profil Perusahaan —</option>
                        @foreach ($publishers as $publisherId => $publisherName)
                            <option value="{{ $publisherId }}" @selected($value('header_publisher_id') === (string) $publisherId)>{{ $publisherName }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Nama Header Manual" name="header_name" hint="Opsional — menimpa nama pada header cetak">
                    <x-input type="text" name="header_name" :value="$value('header_name')" :invalid="$errors->has('header_name')" />
                </x-field>
            </div>
        </x-card>

        <x-card>
            <x-card-header title="Keuangan" icon="percent" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Tarif Pajak (%)" name="tax_rate" hint="Diterapkan saat menerbitkan invoice">
                    <x-input type="number" name="tax_rate" step="any" :value="$value('tax_rate')" :invalid="$errors->has('tax_rate')" />
                </x-field>
                <x-field label="Pajak Buku (%)" name="book_tax_rate" hint="Pajak untuk buku">
                    <x-input type="number" name="book_tax_rate" step="any" :value="$value('book_tax_rate')" :invalid="$errors->has('book_tax_rate')" />
                </x-field>
                <x-field label="Pajak Barang Lainnya (%)" name="goods_tax_rate" hint="Pajak untuk barang lainnya">
                    <x-input type="number" name="goods_tax_rate" step="any" :value="$value('goods_tax_rate')" :invalid="$errors->has('goods_tax_rate')" />
                </x-field>
                <x-field label="PPN (%)" name="ppn_rate" hint="PPN untuk barang lainnya">
                    <x-input type="number" name="ppn_rate" step="any" :value="$value('ppn_rate')" :invalid="$errors->has('ppn_rate')" />
                </x-field>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>
@endsection
