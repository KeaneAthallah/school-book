
@if (auth()->user()?->hasPermission('users.manage'))
    <div class="flex justify-end gap-3">
        <button
            type="button"
            class="text-xs font-medium text-ink-soft transition hover:text-brand-600 hover:underline dark:hover:text-brand-300"
            x-on:click="$dispatch('open-modal', 'edit-user-{{ $row['id'] }}')"
        >Ubah</button>

        <form
            method="POST"
            action="{{ route('users.destroy', $row['id']) }}"
            x-data
            x-on:submit="if (! confirm('Hapus pengguna ini?')) $event.preventDefault()"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-medium text-rose-600 transition hover:underline dark:text-rose-400">Hapus</button>
        </form>
    </div>
@endif
