@php
    use App\Support\Accent;
    use Illuminate\Support\Facades\Route as RouteFacade;

    $user = auth()->user();

    $allowed = fn (?string $permission): bool => $permission === null
        || ($user !== null && $user->hasPermission($permission));

    // A Sales / PIC user can only ever browse their own portfolio, so the entry
    // is labelled accordingly. Anyone who can manage schools sees the neutral
    // label instead. This is presentation only - the record restriction itself
    // is enforced by the server.
    $schoolLabel = $user !== null
        && $user->hasRole('sales')
        && ! $user->hasPermission('schools.manage')
            ? 'Sekolah Saya'
            : 'Sekolah';

    $nav = [
        ['kind' => 'link', 'label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'permission' => 'dashboard.view'],

        ['kind' => 'group', 'label' => 'Pesanan', 'icon' => 'cart', 'permission' => 'orders.view', 'items' => [
            ['label' => 'Nota Pesanan', 'route' => 'orders.index', 'match' => 'orders.index', 'permission' => 'orders.view'],
            ['label' => 'Buat Pesanan', 'route' => 'orders.create', 'match' => 'orders.create', 'permission' => 'orders.manage'],
            ['label' => 'Sumber SIPLah', 'route' => 'orders.index', 'query' => ['source' => 'SIPLAH'], 'match' => 'orders.index', 'permission' => 'orders.view'],
            ['label' => 'Sumber e-Katalog', 'route' => 'orders.index', 'query' => ['source' => 'EKATALOG'], 'match' => 'orders.index', 'permission' => 'orders.view'],
            ['label' => 'Pesanan Manual', 'route' => 'orders.index', 'query' => ['source' => 'MANUAL'], 'match' => 'orders.index', 'permission' => 'orders.view'],
            ['label' => 'Perlu Validasi', 'route' => 'orders.index', 'query' => ['status' => 'received'], 'match' => 'orders.index', 'permission' => 'orders.view'],
            ['label' => 'Menunggu Stok', 'route' => 'orders.index', 'query' => ['status' => 'processing'], 'match' => 'orders.index', 'permission' => 'orders.view'],
        ]],

        ['kind' => 'group', 'label' => 'Master Data', 'icon' => 'database', 'items' => [
            ['label' => $schoolLabel, 'route' => 'master.index', 'params' => ['resource' => 'schools'], 'match' => 'master.*', 'permission' => 'schools.view'],
            ['label' => 'Penerbit', 'route' => 'master.index', 'params' => ['resource' => 'publishers'], 'match' => 'master.*', 'permission' => 'publishers.view'],
            ['label' => 'Buku', 'route' => 'master.index', 'params' => ['resource' => 'books'], 'match' => 'master.*', 'permission' => 'books.view'],
            ['label' => 'Barang Lainnya', 'route' => 'master.index', 'params' => ['resource' => 'barang-lainnya'], 'match' => 'master.*', 'permission' => 'goods.view'],
            ['label' => 'Kategori Buku', 'route' => 'master.index', 'params' => ['resource' => 'categories'], 'match' => 'master.*', 'permission' => 'categories.manage'],
            ['label' => 'Sumber Pesanan', 'route' => 'master.index', 'params' => ['resource' => 'order-sources'], 'match' => 'master.*', 'permission' => 'order-sources.manage'],
            ['label' => 'Kurir', 'route' => 'master.index', 'params' => ['resource' => 'couriers'], 'match' => 'master.*', 'permission' => 'couriers.manage'],
            ['label' => 'Metode Kirim', 'route' => 'master.index', 'params' => ['resource' => 'shipping-methods'], 'match' => 'master.*', 'permission' => 'couriers.manage'],
            ['label' => 'Metode Bayar', 'route' => 'master.index', 'params' => ['resource' => 'payment-methods'], 'match' => 'master.*', 'permission' => 'payment-methods.manage'],
            ['label' => 'Aturan Rebate', 'route' => 'master.index', 'params' => ['resource' => 'rebate-rules'], 'match' => 'master.*', 'permission' => 'rebate-rules.manage'],
            ['label' => 'Konfigurasi', 'route' => 'master.index', 'params' => ['resource' => 'settings'], 'match' => 'master.*', 'permission' => 'settings.manage'],
        ]],

        ['kind' => 'group', 'label' => 'Pengadaan', 'icon' => 'boxes', 'permission' => 'procurement.view', 'items' => [
            ['label' => 'Back Order', 'route' => 'backorders.index', 'match' => 'backorders.index', 'permission' => 'procurement.view'],
            ['label' => 'Rekap Item Tidak Tersedia', 'route' => 'backorders.unavailable', 'match' => 'backorders.unavailable', 'permission' => 'procurement.view'],
            ['label' => 'Penerimaan dari Penerbit', 'route' => 'receipts.index', 'match' => 'receipts.*', 'permission' => 'receipts.view'],
        ]],

        ['kind' => 'group', 'label' => 'Picking & Packing', 'icon' => 'clipboard', 'permission' => 'fulfillment.view', 'items' => [
            ['label' => 'Daftar Picking', 'route' => 'pickings.index', 'match' => 'pickings.*', 'permission' => 'fulfillment.view'],
            ['label' => 'Proses Picking', 'route' => 'pickings.index', 'query' => ['status' => 'draft'], 'match' => 'pickings.index', 'permission' => 'fulfillment.view'],
            ['label' => 'Proses Packing', 'route' => 'pickings.index', 'query' => ['status' => 'picked'], 'match' => 'pickings.index', 'permission' => 'fulfillment.view'],
        ]],

        ['kind' => 'group', 'label' => 'Pengiriman', 'icon' => 'truck', 'permission' => 'shipments.view', 'items' => [
            ['label' => 'Daftar Pengiriman', 'route' => 'shipments.index', 'match' => 'shipments.*', 'permission' => 'shipments.view'],
            ['label' => 'Siap Kirim', 'route' => 'shipments.index', 'query' => ['status' => 'draft'], 'match' => 'shipments.index', 'permission' => 'shipments.view'],
            ['label' => 'Surat Jalan', 'route' => 'shipments.index', 'query' => ['status' => 'shipped'], 'match' => 'shipments.index', 'permission' => 'shipments.view'],
        ]],

        ['kind' => 'link', 'label' => 'Invoice', 'icon' => 'receipt', 'route' => 'invoices.index', 'match' => 'invoices.*', 'permission' => 'invoices.view'],

        ['kind' => 'group', 'label' => 'Piutang', 'icon' => 'wallet', 'permission' => 'invoices.view', 'items' => [
            ['label' => 'Daftar Piutang', 'route' => 'receivables.index', 'match' => 'receivables.index', 'permission' => 'invoices.view'],
            ['label' => 'Barang Belum Dikirim', 'route' => 'finance.hutang.list', 'match' => 'finance.hutang.*', 'permission' => 'invoices.view'],
            ['label' => 'Jatuh Tempo', 'route' => 'receivables.index', 'query' => ['bucket' => 'overdue'], 'match' => 'receivables.index', 'permission' => 'invoices.view'],
            ['label' => 'Riwayat Pembayaran', 'route' => 'payments.index', 'match' => 'payments.*', 'permission' => 'payments.view'],
        ]],

        ['kind' => 'link', 'label' => 'Pinjaman Tunai', 'icon' => 'wallet', 'route' => 'cash-loans.index', 'match' => 'cash-loans.*', 'permission' => 'loans.view'],
        ['kind' => 'link', 'label' => 'Katalog', 'icon' => 'book', 'route' => 'catalogs.index', 'match' => 'catalogs.*', 'permission' => 'catalogs.view'],
        ['kind' => 'link', 'label' => 'Rebate / Cashback', 'icon' => 'percent', 'route' => 'rebates.index', 'match' => 'rebates.*', 'permission' => 'rebates.view'],
        ['kind' => 'link', 'label' => 'Laporan', 'icon' => 'chart', 'route' => 'reports.index', 'match' => 'reports.*', 'permission' => 'reports.view'],

        ['kind' => 'group', 'label' => 'Pengaturan', 'icon' => 'settings', 'items' => [
            ['label' => 'Profil Perusahaan', 'route' => 'settings.company.edit', 'match' => 'settings.company.*', 'permission' => 'settings.manage'],
            ['label' => 'Pengguna', 'route' => 'users.index', 'match' => 'users.*', 'permission' => 'users.view'],
            ['label' => 'Jabatan & Role', 'route' => 'roles.index', 'match' => 'roles.*', 'permission' => 'roles.manage'],
            ['label' => 'Integrasi & Import', 'route' => 'integrations.index', 'match' => 'integrations.*', 'permission' => 'integrations.view'],
            ['label' => 'Audit Log', 'route' => 'audit.index', 'match' => 'audit.*', 'permission' => 'audit.view'],
        ]],
    ];

    $queryKeys = ['source', 'status', 'bucket', 'type'];

    $isActive = function (array $item) use ($queryKeys): bool {
        if (! request()->routeIs($item['match'] ?? '')) {
            return false;
        }

        // Master data entries share the master.* routes, so the active
        // entry is the one whose resource segment is in the URL.
        $resource = $item['params']['resource'] ?? null;
        if ($resource !== null && (string) request()->route('resource') !== (string) $resource) {
            return false;
        }

        $query = $item['query'] ?? [];

        if ($query === []) {
            foreach ($queryKeys as $key) {
                if (request()->query($key) !== null) {
                    return false;
                }
            }

            return true;
        }

        foreach ($query as $key => $value) {
            if ((string) request()->query($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    };

    $href = function (array $item): string {
        $params = $item['params'] ?? [];
        $query = $item['query'] ?? [];
        $url = RouteFacade::has($item['route']) ? route($item['route'], $params) : '#';

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    };
@endphp

<div class="flex h-full flex-col bg-panel dark:bg-navy-900">
    <div class="flex h-header items-center gap-2.5 px-4">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-control bg-brand-600">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-10 object-cover">
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-ink dark:text-white">{{ config('app.name') }}</p>
            <p class="truncate text-[11px] text-ink-muted dark:text-navy-300">Kelola Lebih Mudah, Pantau Lebih Akurat.</p>
        </div>
    </div>

    <nav class="scroll-slim flex-1 overflow-y-auto px-2 pb-4" aria-label="Navigasi utama">
        <ul class="space-y-0.5">
            @foreach ($nav as $entry)
                @php
                    if ($entry['kind'] === 'link') {
                        if (! $allowed($entry['permission'] ?? null)) {
                            continue;
                        }

                        $active = $isActive($entry);
                        @endphp
                        <li>
                            <a
                                href="{{ $href($entry) }}"
                                @if ($active) aria-current="page" @endif
                                class="flex items-center gap-2.5 rounded-control px-3 py-2 text-sm transition {{ $active ? 'bg-brand-600 font-medium text-white' : 'text-ink-soft hover:bg-panel-soft hover:text-ink dark:text-navy-200 dark:hover:bg-white/5 dark:hover:text-white' }}"
                            >
                                <x-icon :name="$entry['icon']" class="h-4.5 w-4.5 shrink-0 {{ Accent::iconColor($entry['icon']) }}" />
                                <span class="truncate">{{ $entry['label'] }}</span>
                            </a>
                        </li>
                        @php
                        continue;
                    }

                    $items = array_values(array_filter(
                        $entry['items'],
                        fn (array $item): bool => $allowed($item['permission'] ?? null),
                    ));

                    if ($items === []) {
                        continue;
                    }

                    $containsActive = false;
                    foreach ($items as $item) {
                        if ($isActive($item)) {
                            $containsActive = true;
                            break;
                        }
                    }
                @endphp

                <li x-data="{ open: {{ $containsActive ? 'true' : 'false' }} }">
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        :aria-expanded="open ? 'true' : 'false'"
                        class="flex w-full items-center gap-2.5 rounded-control px-3 py-2 text-sm transition {{ $containsActive ? 'text-ink dark:text-white' : 'text-ink-soft hover:bg-panel-soft hover:text-ink dark:text-navy-200 dark:hover:bg-white/5 dark:hover:text-white' }}"
                    >
                        <x-icon :name="$entry['icon']" class="h-4.5 w-4.5 shrink-0 {{ Accent::iconColor($entry['icon']) }}" />
                        <span class="flex-1 truncate text-left">{{ $entry['label'] }}</span>
                        <x-icon name="chevronDown" class="h-3.5 w-3.5 shrink-0 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>

                    <ul
                        x-show="open"
                        x-cloak
                        class="mt-0.5 ml-4 space-y-0.5 border-l border-line dark:border-navy-700 pl-2"
                    >
                        @foreach ($items as $item)
                            @php $active = $isActive($item); @endphp
                            <li>
                                <a
                                    href="{{ $href($item) }}"
                                    @if ($active) aria-current="page" @endif
                                    class="block rounded-control px-3 py-1.5 text-[13px] transition {{ $active ? 'bg-brand-50 font-medium text-brand-700 dark:bg-white/10 dark:text-white' : 'text-ink-soft hover:bg-panel-soft hover:text-ink dark:text-navy-300 dark:hover:bg-white/5 dark:hover:text-white' }}"
                                >{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-line dark:border-navy-800 px-4 py-3">
        <p class="text-[11px] text-ink-muted dark:text-navy-400">{{ config('app.timezone') }}</p>
    </div>
</div>
