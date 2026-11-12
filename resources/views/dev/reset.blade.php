@extends('layouts.app')

@section('title', 'Reset Database (Dev)')

@section('content')
    <x-page-header title="Reset Database (Dev)" subtitle="Jalankan migrate:fresh --seed. Hanya tersedia di environment local/dev/testing." icon="database">
        <x-slot:actions>
            <x-button :href="route('dashboard')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <div class="space-y-4 p-4">
            <p class="text-sm text-ink">
                Tindakan ini akan <strong>menghapus seluruh data</strong> dan menjalankan ulang migrasi + seeder.
                Hanya gunakan pada environment pengembangan/pengujian.
            </p>

            <form method="POST" action="{{ route('dev.reset') }}" class="space-y-3">
                @csrf
                <x-field label="Ketik RESET untuk konfirmasi" name="confirmation">
                    <x-input type="text" name="confirmation" placeholder="RESET" :invalid="$errors->has('confirmation')" />
                </x-field>

                <div class="flex justify-end gap-2">
                    <x-button type="submit" variant="danger">Reset Database</x-button>
                </div>
            </form>
        </div>
    </x-card>
@endsection