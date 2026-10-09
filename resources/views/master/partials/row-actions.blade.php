
@php
    $slug = request()->route('resource');
@endphp

<div class="flex justify-end gap-3">
    <a
        href="{{ route('master.edit', [$slug, $row['id']]) }}"
        class="text-xs font-medium text-ink-soft transition hover:text-brand-600 hover:underline dark:hover:text-brand-300"
    >Ubah</a>

    <form
        method="POST"
        action="{{ route('master.destroy', [$slug, $row['id']]) }}"
        x-data
        x-on:submit="if (! confirm('Hapus data ini?')) $event.preventDefault()"
    >
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-medium text-rose-600 transition hover:underline dark:text-rose-400">Hapus</button>
    </form>
</div>
