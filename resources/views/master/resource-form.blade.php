@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $isEdit = $record !== null;
        $action = $isEdit
            ? route('master.update', [$resource['slug'], $record->getKey()])
            : route('master.store', $resource['slug']);
        $indexRoute = route('master.index', $resource['slug']);
    @endphp

    <x-page-header :title="$title" :subtitle="$resource['title']">
        <x-slot:actions>
            <x-button :href="$indexRoute" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @include('master.partials.fields', [
                'resource' => $resource,
                'fields' => $resource['fields'],
                'values' => $values,
            ])

            <div class="flex justify-end gap-2 border-t border-line px-4 py-3">
                <x-button :href="$indexRoute" variant="secondary">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-card>
@endsection
