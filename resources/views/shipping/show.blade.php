@extends('layouts.app')

@section('title', 'Pengiriman '.$shipment['number'])

@section('content')
    @php
        use App\Support\Format;

        $detail = [
            'Pesanan' => $shipment['order'] ?? '-',
            'Kurir / Metode' => trim(($shipment['courier'] ?? '').' / '.($shipment['shipping_method'] ?? ''), ' /') ?: '-',
            'No. Resi' => $shipment['tracking_number'] ?? '-',
            'Penerima' => $shipment['recipient_name'] ?? '-',
            'Alamat' => $shipment['address'] ?? '-',
            'Tgl Kirim' => $shipment['ship_date'] ? Format::date($shipment['ship_date']) : '-',
            'Dikonfirmasi' => $shipment['confirmed_at'] ? Format::dateTime($shipment['confirmed_at']) : '-',
        ];
    @endphp

    <x-page-header
        :title="'Pengiriman '.$shipment['number']"
        :subtitle="'Surat Jalan '.($shipment['delivery_note_number'] ?? '-').' · '.($shipment['school'] ?? '')"
        icon="truck"
    >
        <x-slot:actions>
            @if ($shipment['delivery_note_number'])
                <x-button :href="route('shipments.print', $shipment['id'])" variant="secondary" target="_blank" rel="noreferrer">
                    <x-icon name="printer" class="h-4 w-4" />
                    Cetak Surat Jalan
                </x-button>
            @endif

            @if ($can['confirm'])
                <form method="POST" action="{{ route('shipments.confirm', $shipment['id']) }}" enctype="multipart/form-data" x-data x-on:submit="if (! confirm('Konfirmasi pengiriman? Stok akan berkurang.')) $event.preventDefault()" class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                    @csrf
                    <div x-data="photoPreview" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <input type="file" name="proof_photo" accept="image/jpeg,image/png,image/webp" required
                            aria-label="Bukti foto pengiriman"
                            class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                            @change="pick($event)">
                        <img x-show="preview" :src="preview" alt="Pratinjau bukti foto pengiriman" class="h-20 w-20 self-start rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                    </div>
                    <x-button type="submit">Konfirmasi Kirim</x-button>
                </form>
            @endif

            @if ($can['deliver'])
                <form method="POST" action="{{ route('shipments.deliver', $shipment['id']) }}">
                    @csrf
                    <x-button type="submit" variant="secondary">Tandai Diterima</x-button>
                </form>
            @endif

            @if ($can['invoice'])
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf
                    <input type="hidden" name="shipment_id" value="{{ $shipment['id'] }}" />
                    <x-button type="submit" variant="secondary">Terbitkan Invoice</x-button>
                </form>
            @endif

            <x-button :href="route('shipments.index')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Item" icon="boxes" />
            <x-data-table
                empty="Pengiriman ini belum memiliki item."
                empty-icon="boxes"
                :rows="collect($shipment['items'])->map(fn ($item, $index) => ['id' => $index] + $item)->all()"
                :columns="[
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'product', 'label' => 'Barang'],
                    ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Detail" icon="truck">
                <x-slot:action>
                    <x-status-badge :value="$shipment['status']" :label="$shipment['status_label']" />
                </x-slot:action>
            </x-card-header>
            <dl class="space-y-3 p-4 text-sm">
                @foreach ($detail as $label => $value)
                    <div>
                        <dt class="text-xs text-ink-soft">{{ $label }}</dt>
                        <dd class="text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt class="text-xs text-ink-soft">Catatan</dt>
                    <dd class="text-ink">{{ $shipment['notes'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Bukti Pengiriman" subtitle="Bukti kirim & bukti terima barang" icon="checkCircle">
            <x-slot:action>
                @if ($can['attach'])
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <form method="POST" action="{{ route('shipments.attachments.store', $shipment['id']) }}" enctype="multipart/form-data" class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                            @csrf
                            <input type="hidden" name="type" value="delivery" />
                            <div x-data="photoPreview" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                                    aria-label="Bukti kirim barang"
                                    class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                                    @change="pick($event)">
                                <img x-show="preview" :src="preview" alt="Pratinjau" class="h-20 w-20 self-start rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                            </div>
                            <x-button type="submit" variant="secondary">Upload Bukti Kirim</x-button>
                        </form>
                        <form method="POST" action="{{ route('shipments.attachments.store', $shipment['id']) }}" enctype="multipart/form-data" class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                            @csrf
                            <input type="hidden" name="type" value="receipt" />
                            <div x-data="photoPreview" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                                    aria-label="Bukti terima barang"
                                    class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                                    @change="pick($event)">
                                <img x-show="preview" :src="preview" alt="Pratinjau" class="h-20 w-20 self-start rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                            </div>
                            <x-button type="submit" variant="secondary">Upload Bukti Terima</x-button>
                        </form>
                    </div>
                @endif
            </x-slot:action>
        </x-card-header>

        <div class="p-4 space-y-4">
            @php
                $delivery = collect($shipment['attachments'])->where('type', 'delivery')->values();
                $receipt = collect($shipment['attachments'])->where('type', 'receipt')->values();
            @endphp

            <div>
                <p class="mb-2 text-sm font-medium text-ink">Bukti Kirim Barang</p>
                @if ($delivery->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($delivery as $attachment)
                            <figure class="group relative overflow-hidden rounded-control border border-line bg-canvas">
                                <a href="{{ $attachment['url'] }}" target="_blank" rel="noreferrer">
                                    <img src="{{ $attachment['url'] }}" alt="{{ $attachment['name'] }}" loading="lazy" class="h-36 w-full object-cover">
                                </a>
                                <figcaption class="truncate px-2 py-1.5 text-[11px] text-ink-soft">{{ $attachment['name'] }}</figcaption>
                                @if ($can['manage_attachments'])
                                    <form method="POST" action="{{ route('attachments.destroy', $attachment['id']) }}" class="absolute right-1.5 top-1.5">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus bukti foto"
                                            class="rounded-control bg-rose-600/90 p-1 text-white opacity-0 transition hover:bg-rose-600 group-hover:opacity-100">
                                            <x-icon name="close" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-ink-soft">Belum ada bukti kirim.</p>
                @endif
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-ink">Bukti Terima Barang</p>
                @if ($receipt->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($receipt as $attachment)
                            <figure class="group relative overflow-hidden rounded-control border border-line bg-canvas">
                                <a href="{{ $attachment['url'] }}" target="_blank" rel="noreferrer">
                                    <img src="{{ $attachment['url'] }}" alt="{{ $attachment['name'] }}" loading="lazy" class="h-36 w-full object-cover">
                                </a>
                                <figcaption class="truncate px-2 py-1.5 text-[11px] text-ink-soft">{{ $attachment['name'] }}</figcaption>
                                @if ($can['manage_attachments'])
                                    <form method="POST" action="{{ route('attachments.destroy', $attachment['id']) }}" class="absolute right-1.5 top-1.5">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus bukti foto"
                                            class="rounded-control bg-rose-600/90 p-1 text-white opacity-0 transition hover:bg-rose-600 group-hover:opacity-100">
                                            <x-icon name="close" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-ink-soft">Belum ada bukti terima.</p>
                @endif
            </div>
        </div>
    </x-card>
@endsection
