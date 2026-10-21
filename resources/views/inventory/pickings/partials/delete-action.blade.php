<form method="POST" action="{{ route('pickings.destroy', $row['id']) }}" x-data x-on:submit="if (! confirm('Hapus picking ini?')) $event.preventDefault()" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
</form>