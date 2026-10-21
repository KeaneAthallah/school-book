<a href="{{ route('backorders.orderShow', $row['id']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Detail</a>
<span class="text-ink-muted">·</span>
<a href="{{ route('backorders.orderEdit', $row['id']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Ubah</a>
<span class="text-ink-muted">·</span>
<a href="{{ route('backorders.orderPrint', $row['id']) }}" target="_blank" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Cetak</a>
<span class="text-ink-muted">·</span>
<form method="POST" action="{{ route('backorders.orderDestroy', $row['id']) }}" class="inline" x-data x-on:submit="if (! confirm('Hapus pesanan ke penerbit ini? Back order kembali ke daftar.')) $event.preventDefault()">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
</form>