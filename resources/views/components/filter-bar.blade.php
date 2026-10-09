@props(['method' => 'get'])

<form
    method="{{ $method === 'get' ? 'get' : 'post' }}"
    {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-2 border-b border-line px-4 py-3']) }}
>
    @if ($method !== 'get')
        @csrf
        @method($method)
    @endif

    {{ $slot }}
</form>
