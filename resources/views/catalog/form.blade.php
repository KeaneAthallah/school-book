@extends('layouts.app')

@section('title', isset($catalog) ? 'Ubah Katalog' : 'Tambah Katalog')

@section('content')
    <x-page-header
        :title="isset($catalog) ? 'Ubah Katalog' : 'Tambah Katalog'"
        subtitle="Susun daftar produk dan harganya untuk dicetak atau dibagikan ke sekolah."
        icon="book"
    >
        <x-slot:actions>
            <x-button :href="route('catalogs.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ isset($catalog) ? route('catalogs.update', $catalog) : route('catalogs.store') }}" class="space-y-4">
        @csrf
        @if (isset($catalog)) @method('PUT') @endif

        <x-card>
            <x-card-header title="Informasi" icon="book" />
            <div class="grid gap-3 p-4 sm:grid-cols-2">
                <x-field label="Nama" name="name" required>
                    <x-input name="name" value="{{ old('name', $catalog->name ?? '') }}" :invalid="$errors->has('name')" />
                </x-field>

                <x-field label="Deskripsi" name="description">
                    <x-textarea name="description" rows="2">{{ old('description', $catalog->description ?? '') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <x-card
            x-data="productPicker({{ json_encode([
                'products' => $products,
                'selected' => old('product_ids', $selected ?? []),
            ]) }})"
        >
            <x-card-header title="Produk" icon="boxes" />
            <div class="space-y-4 p-4">
                <div
                    class="relative flex flex-col gap-2 sm:flex-row"
                    @click.outside="showDropdown = false"
                >
                    <input
                        type="text"
                        x-ref="picker"
                        x-model="query"
                        @focus="showDropdown = true"
                        placeholder="Cari produk berdasarkan kode atau judul…"
                        autocomplete="off"
                        class="w-full flex-1 rounded-control border border-line-strong bg-panel px-3 py-2 text-sm text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25"
                    />

                    <select
                        x-model="category"
                        @focus="showDropdown = true"
                        aria-label="Filter kategori"
                        class="rounded-control border border-line-strong bg-panel px-3 py-2 text-sm text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25 sm:w-60"
                    >
                        <option value="">Semua Kategori</option>
                        <template x-for="name in categories" :key="name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>

                    <div
                        x-show="showDropdown"
                        class="absolute top-full right-0 left-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-control border border-line-strong bg-panel shadow-lg"
                    >
                        <template x-for="product in available" :key="product.id">
                            <div
                                class="flex cursor-pointer items-center justify-between gap-3 px-3 py-2 text-sm transition-colors hover:bg-panel-soft"
                                @click="add(product.id)"
                            >
                                <span class="min-w-0 truncate text-ink">
                                    <span class="font-medium" x-text="product.code"></span>
                                    <span class="text-ink-soft" x-text="'· ' + product.title"></span>
                                </span>
                                <span class="hidden shrink-0 text-xs text-ink-muted md:inline" x-text="categoryOf(product)"></span>
                                <span class="shrink-0 text-xs tabular-nums text-ink-muted" x-text="money(product.price)"></span>
                            </div>
                        </template>

                        <p x-show="available.length === 0" class="px-3 py-2 text-sm text-ink-muted">
                            Tidak ada produk untuk ditambahkan.
                        </p>
                    </div>
                </div>

                <template x-for="product in selectedProducts" :key="product.id">
                    <input type="hidden" name="product_ids[]" :value="product.id" />
                </template>

                <div>
                    <p class="mb-2 text-xs font-medium text-ink-soft">
                        Produk dipilih (<span x-text="selected.length"></span>)
                    </p>

                    <div
                        x-show="selected.length === 0"
                        class="rounded-control border border-dashed border-line-strong px-3 py-2 text-sm text-ink-muted"
                    >
                        Belum ada produk dipilih. Gunakan kolom pencarian di atas.
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="product in selectedProducts" :key="product.id">
                            <span class="inline-flex max-w-full items-center gap-2 rounded-control border border-line bg-panel-soft py-1 pr-1 pl-2.5 text-xs text-ink">
                                <span class="truncate" x-text="`${product.code} - ${product.title}`"></span>
                                <button
                                    type="button"
                                    class="rounded-control px-1 font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-500/15"
                                    @click="remove(product.id)"
                                    aria-label="Hapus produk"
                                >&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                @error('product_ids')
                    <p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button :href="route('catalogs.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>
@endsection
