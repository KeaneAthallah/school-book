<form method="POST" action="{{ route('overtimes.destroy', $row['id']) }}" onsubmit="return confirm('Hapus lembur ini?')">
    @csrf
    @method('DELETE')
    <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
</form>