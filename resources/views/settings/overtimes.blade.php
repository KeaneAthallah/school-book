@extends('layouts.app')
@section('title', 'Jam Lembur')
@section('content')
    <x-page-header title="Jam Lembur" subtitle="Tambahan jam kerja di luar jam kerja default." icon="clock">
        <x-slot:actions>
            <x-button :href="route('users.index')" variant="secondary">Kembali ke Pengguna</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <x-card-header title="Tambah Lembur" icon="plus" />
        <form method="POST" action="{{ route('overtimes.store') }}" class="grid gap-3 p-4 sm:grid-cols-5">
            @csrf
            <x-field label="Pengguna" name="user_id" required>
                <x-select name="user_id" :invalid="$errors->has('user_id')">
                    <option value="">— Pilih —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Tanggal" name="date" required>
                <x-input type="date" name="date" value="{{ old('date') }}" :invalid="$errors->has('date')" />
            </x-field>
            <x-field label="Mulai" name="start_time" required>
                <x-input type="time" name="start_time" value="{{ old('start_time') }}" :invalid="$errors->has('start_time')" />
            </x-field>
            <x-field label="Selesai" name="end_time" required>
                <x-input type="time" name="end_time" value="{{ old('end_time') }}" :invalid="$errors->has('end_time')" />
            </x-field>
            <x-field label="Alasan" name="reason">
                <x-input type="text" name="reason" value="{{ old('reason') }}" :invalid="$errors->has('reason')" />
            </x-field>
            <div class="sm:col-span-5 flex justify-end">
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        <x-card-header title="Daftar Lembur" icon="clock" />
        <x-data-table
            empty="Belum ada lembur."
            :rows="$overtimes"
            :columns="[
                ['key' => 'user', 'label' => 'Pengguna', 'view' => 'settings.partials.overtime-user'],
                ['key' => 'date', 'label' => 'Tanggal'],
                ['key' => 'start_time', 'label' => 'Mulai'],
                ['key' => 'end_time', 'label' => 'Selesai'],
                ['key' => 'reason', 'label' => 'Alasan'],
                ['label' => 'Aksi', 'view' => 'settings.partials.overtime-actions'],
            ]"
        />
    </x-card>
    <div class="mt-3">{{ $overtimes->links() }}</div>
@endsection