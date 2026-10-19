@extends('layouts.app')
@section('title', 'Back Order')
@section('content')
    <x-page-header title="Back Order" subtitle="Daftar back order dan penerbit yang diorder." icon="boxes" />
    <x-card>
        <x-data-table
            empty="Belum ada back order."
            :rows="$backorders"
            :columns="[
                ['key' => 'id', 'label' => 'ID'],
            ]"
        />
    </x-card>
    <div class="mt-3">{{ $backorders->links() }}</div>
    <x-card class="mt-4">
        <h3 class="mb-2 font-medium">Tandai Diorder ke Penerbit</h3>
        <form method="POST" action="{{ route('backorders.orderStore') }}" class="grid gap-2 sm:grid-cols-4">
            @csrf
            <x-input type="number" name="backorder_id" placeholder="Backorder ID" />
            <x-select name="publisher_id">
                @foreach ($publishers as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </x-select>
            <x-input type="number" name="quantity" placeholder="Qty" />
            <x-button type="submit">Simpan</x-button>
        </form>
    </x-card>
@endsection