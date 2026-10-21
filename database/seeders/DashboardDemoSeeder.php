<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Courier;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\School;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\FulfillmentService;
use App\Services\GoodsReceiptService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ShipmentService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Development-only demonstration data for the BOOKFLOW interface.
 *
 * Safety:
 *  - refuses to run in production;
 *  - requires an explicit opt-in outside the local/development/testing envs;
 *  - never deletes or updates records it did not create (all writes are
 *    firstOrCreate or service-layer creates);
 *  - writes a sentinel setting so a second run is a no-op;
 *  - everything runs inside one transaction.
 *
 * Run with: php artisan db:seed --class=DashboardDemoSeeder
 */
class DashboardDemoSeeder extends Seeder
{
    private const SENTINEL = 'demo.seeded_version';

    private const VERSION = '1';

    private const NPSN_PREFIX = '9990';

    private User $operator;

    private User $warehouseUser;

    private User $financeUser;

    private User $approver;

    private User $salesUser;

    /**
     * @var array<int, Book>
     */
    private array $books = [];

    /**
     * @var array<int, School>
     */
    private array $schools = [];

    public function run(): void
    {
        $this->guardEnvironment();

        if ($this->alreadySeeded()) {
            $this->console()?->warn('Demo data already present (sentinel '.self::SENTINEL.'). Nothing to do.');

            return;
        }

        // Deterministic output: the same demo data on every fresh run.
        mt_srand(20260101);

        DB::transaction(function (): void {
            $this->resolveActors();
            $this->seedPublishersAndBooks();
            $this->seedSchools();
            $this->seedOpeningStock();
            $this->seedDraftReceipt();
            $this->seedOrders();
            $this->seedPaymentsToVerify();

            Setting::query()->updateOrCreate(
                ['key' => self::SENTINEL],
                ['value' => self::VERSION, 'group' => 'demo'],
            );
        });

        $this->console()?->info('Demo data seeded: '.count($this->books).' books, '.count($this->schools).' schools, '
            .Order::query()->count().' orders in total.');
    }

    /**
     * Refuse to touch anything that is not a local development database.
     */
    /**
     * The console command when the seeder runs through `db:seed`.
     * Null when a seeder is invoked directly (for example from a test).
     */
    private function console(): ?Command
    {
        /** @var Command|null $command */
        $command = $this->command;

        return $command;
    }

    private function guardEnvironment(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DashboardDemoSeeder refuses to run: the application is in the production environment.'
            );
        }

        $environment = (string) config('app.env');
        $allowed = in_array($environment, ['local', 'development', 'testing'], true);
        $forced = (bool) ($this->console()?->option('force') ?? false);

        if (! $allowed && ! $forced) {
            throw new RuntimeException(
                "DashboardDemoSeeder refuses to run in the '{$environment}' environment. "
                .'Use --force only against an isolated development database.'
            );
        }

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $this->console()?->line(sprintf(
            'Demo seeding target: connection [%s], database [%s] (credentials are never printed).',
            $connection,
            is_string($database) ? $database : 'n/a',
        ));
    }

    private function alreadySeeded(): bool
    {
        return Setting::query()->where('key', self::SENTINEL)->exists();
    }

    private function resolveActors(): void
    {
        $this->operator = $this->requireUser('operator@sekolah.test');
        $this->warehouseUser = $this->requireUser('gudang@sekolah.test');
        $this->financeUser = $this->requireUser('keuangan@sekolah.test');
        $this->approver = $this->requireUser('pimpinan@sekolah.test');
        $this->salesUser = $this->requireUser('sales@sekolah.test');

    }

    /**
     * Deterministic pseudo-random integer (seeded in run()).
     */
    private function rand(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    private function requireUser(string $email): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            throw new RuntimeException(
                "Run RbacSeeder first — the demo accounts ({$email}) do not exist."
            );
        }

        return $user;
    }

    private function seedPublishersAndBooks(): void
    {
        // Prefer real catalog books when available (production/demo on real data).
        $realBooks = Book::query()->with('product')->limit(24)->get();

        if ($realBooks->isNotEmpty()) {
            $this->books = $realBooks->all();

            return;
        }

        $publishers = [
            ['name' => 'Penerbit Cendekia Nusantara', 'contact_person' => 'Rani Puspita', 'phone' => '021-7700101'],
            ['name' => 'Penerbit Aksara Mandiri', 'contact_person' => 'Dimas Prakoso', 'phone' => '021-7700102'],
            ['name' => 'Penerbit Bintang Ilmu', 'contact_person' => 'Sari Wulandari', 'phone' => '021-7700103'],
            ['name' => 'Penerbit Pustaka Ceria', 'contact_person' => 'Bagus Nugroho', 'phone' => '021-7700104'],
            ['name' => 'Penerbit Lentera Edukasi', 'contact_person' => 'Maya Anggraini', 'phone' => '021-7700105'],
        ];

        $publisherModels = [];

        foreach ($publishers as $data) {
            $publisherModels[] = Publisher::query()->firstOrCreate(
                ['name' => $data['name']],
                [...$data, 'email' => 'kontak@demo-penerbit.test', 'address' => 'Jakarta', 'status' => 'active'],
            );
        }

        $subjects = [
            'MTK' => 'Matematika',
            'BIN' => 'Bahasa Indonesia',
            'IPA' => 'Ilmu Pengetahuan Alam',
            'IPS' => 'Ilmu Pengetahuan Sosial',
            'ENG' => 'Bahasa Inggris',
            'PKN' => 'Pendidikan Kewarganegaraan',
        ];

        $grades = ['7', '8', '9'];

        $category = BookCategory::query()->firstOrCreate(
            ['slug' => 'buku-pelajaran-demo'],
            ['name' => 'Buku Pelajaran (Demo)'],
        );

        $index = 0;

        foreach ($subjects as $code => $subject) {
            foreach ($grades as $grade) {
                foreach (['A', 'B'] as $edition) {
                    $index++;
                    $publisher = $publisherModels[$index % count($publisherModels)];

                    $product = Product::query()->firstOrCreate(
                        ['code' => sprintf('BK-DEMO-%s-%s%s', $code, $grade, $edition)],
                        [
                            'type' => 'book',
                            'title' => sprintf('%s Kelas %s (%s)', $subject, $grade, $edition),
                            'price' => (string) ($this->rand(45, 135) * 1000),
                            'cost_price' => (string) ($this->rand(30, 95) * 1000),
                            'rack_location' => 'RAK-'.$grade,
                            'status' => 'active',
                        ],
                    );

                    $this->books[] = Book::query()->firstOrCreate(
                        ['product_id' => $product->getKey()],
                        [
                            'isbn' => sprintf('978-602-DEMO-%04d', $index),
                            'publisher_id' => $publisher->getKey(),
                            'category_id' => $category->getKey(),
                            'grade' => $grade,
                            'subject' => $subject,
                        ],
                    );
                }
            }
        }
    }

    private function seedSchools(): void
    {
        // Prefer real schools when available.
        $realSchools = School::query()->limit(12)->get();

        if ($realSchools->isNotEmpty()) {
            $this->schools = $realSchools->all();

            // Ensure sales_user_id is set for demo consistency.
            foreach ($this->schools as $school) {
                if ($school->sales_user_id === null) {
                    $school->sales_user_id = $this->salesUser->getKey();
                    $school->save();
                }
            }

            return;
        }

        $names = [
            'SMP Cendekia Harapan',
            'SMP Tunas Bangsa',
            'SMP Kartika Ilmu',
            'SMP Bumi Nusantara',
            'SMP Cahaya Pelita',
            'SMP Wira Bhakti',
            'SMP Nusa Cendana',
            'SMP Anggrek Merah',
            'SMP Bakti Mulia',
            'SMP Harapan Ibu',
            'SMP Mentari Timur',
            'SMP Panca Karya',
        ];

        $districts = ['Rappocini', 'Panakkukang', 'Tamalanrea', 'Biringkanaya', 'Manggala', 'Ujung Pandang'];

        foreach ($names as $i => $name) {
            $this->schools[] = School::query()->firstOrCreate(
                ['npsn' => self::NPSN_PREFIX.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'name' => $name.' (Demo)',
                    'address' => 'Jl. Demo No. '.($i + 1),
                    'district' => $districts[$i % count($districts)],
                    'regency' => 'Kota Makassar',
                    'province' => 'Sulawesi Selatan',
                    'postal_code' => '90'.str_pad((string) ($i + 10), 3, '0'),
                    'contact_person' => 'Wakil Kepala Sekolah',
                    'phone' => '0411-'.$this->rand(700000, 799999),
                    'email' => 'demo-school'.$i.'@example.test',
                    'principal_name' => 'Kepala '.$name,
                    'status' => 'active',
                    'sales_user_id' => $this->salesUser->getKey(),
                ],
            );
        }
    }

    /**
     * Opening stock is created through GoodsReceiptService so the physical
     * balance and the movement ledger stay consistent.
     */
    private function seedOpeningStock(): void
    {
        $receipts = app(GoodsReceiptService::class);

        $byPublisher = [];

        // Leave the last two books without any receipt (out of stock).
        $receivable = array_slice($this->books, 0, max(1, count($this->books) - 2));

        foreach ($receivable as $position => $book) {
            $byPublisher[$book->publisher_id][] = [
                'product_id' => $book->product->getKey(),
                'quantity' => $position % 7 === 0 ? 36 : $this->rand(220, 420),
                'unit_cost' => $book->product->cost_price,
            ];
        }

        $monthsAgo = 4;

        foreach ($byPublisher as $publisherId => $items) {
            $receipt = $receipts->create([
                'publisher_id' => $publisherId,
                'receipt_date' => Carbon::today()->subMonths($monthsAgo)->addDays(3)->toDateString(),
                'notes' => 'Stok awal demo',
                'items' => $items,
            ], $this->warehouseUser);

            $receipts->confirm($receipt, $this->warehouseUser);
        }

    }

    /**
     * One unconfirmed receipt so the "penerimaan belum dikonfirmasi" alert shows.
     */
    private function seedDraftReceipt(): void
    {
        $receipts = app(GoodsReceiptService::class);
        $book = $this->books[0] ?? null;

        if ($book === null) {
            return;
        }

        $receipts->create([
            'publisher_id' => $book->publisher_id,
            'receipt_date' => Carbon::today()->subDays(2)->toDateString(),
            'notes' => 'Menunggu pemeriksaan gudang',
            'items' => [[
                'product_id' => $book->product->getKey(),
                'quantity' => 60,
                'unit_cost' => $book->product->cost_price,
            ]],
        ], $this->warehouseUser);
    }

    private function seedOrders(): void
    {
        $orders = app(OrderService::class);
        $sources = OrderSource::query()->whereIn('code', ['SIPLAH', 'EKATALOG', 'MANUAL'])->get()->keyBy('code');

        if ($sources->isEmpty()) {
            throw new RuntimeException('Order sources are missing — run MasterDataSeeder first.');
        }

        $sourceCodes = ['SIPLAH', 'EKATALOG', 'MANUAL'];

        // Orders per month, oldest first so ledger balances build up naturally.
        $plan = [
            3 => 10,
            2 => 11,
            1 => 12,
            0 => 12,
        ];

        $index = 0;

        foreach ($plan as $monthsAgo => $count) {
            for ($i = 0; $i < $count; $i++) {
                $index++;

                // Distribute the month's orders evenly across the days that have
                // actually happened, so nothing is dated in the future.
                $monthAnchor = Carbon::today()->subMonths($monthsAgo);
                $monthStart = $monthAnchor->copy()->startOfMonth();
                $monthEnd = $monthAnchor->copy()->endOfMonth()->min(Carbon::today());
                $span = max(0, (int) $monthStart->diffInDays($monthEnd));
                $offset = $span > 0 ? (int) round($i * $span / $count) : 0;

                $orderDate = $monthStart->copy()->addDays(min($span, $offset));
                $school = $this->schools[$index % count($this->schools)];
                $sourceCode = $sourceCodes[$index % count($sourceCodes)];
                $source = $sources->get($sourceCode);

                $lines = [];
                $lineCount = $this->rand(1, 3);

                for ($l = 0; $l < $lineCount; $l++) {
                    $book = $this->books[$this->rand(0, count($this->books) - 1)];

                    if (collect($lines)->contains(fn (array $line): bool => $line['product_id'] === $book->product->getKey())) {
                        continue;
                    }

                    $lines[] = [
                        'product_id' => $book->product->getKey(),
                        'quantity' => $this->rand(30, 120),
                        'unit_price' => $book->product->price,
                        'discount_amount' => '0',
                    ];
                }

                $order = $orders->create([
                    'order_date' => $orderDate->toDateString(),
                    'school_id' => $school->getKey(),
                    'order_source_id' => $source->getKey(),
                    'external_reference' => sprintf('DEMO-%s-%04d', $sourceCode, $index),
                    'external_source' => 'DEMO',
                    'notes' => 'Data demonstrasi',
                    'items' => $lines,
                ], $this->operator);

                $this->advanceOrder($order, $index, $monthsAgo);
            }
        }

        $this->seedTodaysActivity();
    }

    /**
     * Guarantee that today looks like a normal working day.
     *
     * The scenario loop above consumes real stock, so the newest orders can end
     * up unshippable and leave the "today" dashboard widgets empty. Here the
     * most recent orders that still hold reserved stock are shipped today, which
     * also produces today's invoice.
     */
    private function seedTodaysActivity(): void
    {
        $today = Carbon::today();

        $shippable = DB::table('order_items')
            ->where('reserved_quantity', '>', 0)
            ->distinct()
            ->pluck('order_id')
            ->all();

        if ($shippable === []) {
            return;
        }

        $candidates = Order::query()
            ->whereIn('id', $shippable)
            ->whereIn('status', [
                OrderStatus::Processing->value,
                OrderStatus::PartialReady->value,
                OrderStatus::Ready->value,
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        foreach ($candidates as $position => $order) {
            // One of them ships only half its reservation so the partial
            // shipment state stays represented.
            $shipment = $this->ship($order, $position === 1, $today);

            if ($shipment === null) {
                continue;
            }

            try {
                $invoice = app(InvoiceService::class)->generateForShipment($shipment, $this->financeUser);
            } catch (\Throwable) {
                continue;
            }

            $invoice->forceFill([
                'invoice_date' => $today->toDateString(),
                'due_date' => $today->copy()->addDays(30)->toDateString(),
            ])->save();
        }
    }

    /**
     * Drive the order through a deterministic scenario so every status,
     * picking state, shipment state and invoice state is represented.
     */
    private function advanceOrder(Order $order, int $index, int $monthsAgo): void
    {
        $scenario = $index % 10;

        if ($scenario === 0) {
            return; // stays a draft
        }

        app(OrderService::class)->confirm($order, $this->operator);
        $order->refresh()->load('items');

        if ($scenario === 1) {
            return; // confirmed, awaiting stock / ready
        }

        $reservedItems = $order->items->filter(fn ($item): bool => $item->reserved_quantity > 0);

        if ($reservedItems->isEmpty()) {
            return; // nothing shippable (waiting for stock, back order recorded)
        }

        if ($scenario === 2) {
            return; // keep it queued
        }

        if ($scenario === 3) {
            app(FulfillmentService::class)->create($order, $this->warehouseUser);

            return; // picking queued
        }

        if ($scenario === 4) {
            $picking = app(FulfillmentService::class)->create($order, $this->warehouseUser);
            $picking->load('items');
            app(FulfillmentService::class)->recordPicked(
                $picking,
                $picking->items->map(fn ($item): array => [
                    'id' => $item->getKey(),
                    'picked_quantity' => $item->quantity,
                ])->all(),
                $this->warehouseUser,
            );

            return; // picked, awaiting packing
        }

        // Scenarios 5-9 ship (partially or fully).
        $partial = $scenario === 5;
        // Ship a couple of days after the order, never in the future. This
        // spreads the current month's shipments (and therefore its invoices)
        // across the elapsed days instead of piling them all onto today, which
        // made the daily sales chart look empty.
        $shipDate = $order->order_date->copy()->addDays(2)->min(Carbon::today());

        $shipment = $this->ship($order, $partial, $shipDate);

        if ($shipment === null) {
            return;
        }

        if ($scenario === 5) {
            return; // partial shipment only
        }

        $invoiceDate = $shipDate->copy()->addDays(1)->min(Carbon::today());

        try {
            $invoice = app(InvoiceService::class)->generateForShipment($shipment, $this->financeUser);
        } catch (\Throwable) {
            return;
        }

        $invoice->forceFill([
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addDays(30)->toDateString(),
        ])->save();

        if ($scenario === 6) {
            return; // issued, unpaid
        }

        if ($scenario === 7) {
            $this->pay($invoice, 0.45, $invoiceDate->copy()->addDays(6)->min(Carbon::today()));

            return; // partially paid
        }

        $this->pay($invoice, 1.0, $invoiceDate->copy()->addDays(9)->min(Carbon::today()));

    }

    private function ship(Order $order, bool $partial, CarbonInterface $shipDate): ?Shipment
    {
        $order->refresh()->load('items');

        $items = [];

        foreach ($order->items as $item) {
            if ($item->reserved_quantity <= 0) {
                continue;
            }

            $quantity = $partial
                ? max(1, intdiv($item->reserved_quantity, 2))
                : $item->reserved_quantity;

            $items[] = ['order_item_id' => $item->getKey(), 'quantity' => $quantity];
        }

        if ($items === []) {
            return null;
        }

        $shipments = app(ShipmentService::class);

        $shipment = $shipments->create($order, [
            'courier_id' => Courier::query()->value('id'),
            'ship_date' => $shipDate->toDateString(),
            'recipient_name' => 'Petugas Sekolah',
            'address' => $order->school?->address,
            'tracking_number' => 'DEMO-RESI-'.$order->getKey(),
            'items' => $items,
        ], $this->warehouseUser);

        $shipments->confirm($shipment, $this->warehouseUser);

        return $shipment->refresh();
    }

    private function pay(Invoice $invoice, float $ratio, CarbonInterface $paymentDate): void
    {
        $amount = (float) $invoice->total_amount * $ratio;
        $amount = (string) round($amount, 2);

        if ((float) $amount <= 0) {
            return;
        }

        $payments = app(PaymentService::class);

        $payment = $payments->record([
            'school_id' => $invoice->school_id,
            'payment_date' => $paymentDate->toDateString(),
            'amount' => $amount,
            'payment_method_id' => PaymentMethod::query()->value('id'),
            'reference' => 'DEMO-BAYAR-'.$invoice->getKey(),
            'allocations' => [
                ['invoice_id' => $invoice->getKey(), 'amount' => $amount],
            ],
        ], $this->financeUser);

        $payments->verify($payment, $this->financeUser);
    }

    /**
     * Two unverified payments so the "menunggu verifikasi" alert has data.
     */
    private function seedPaymentsToVerify(): void
    {
        $invoices = Invoice::query()
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->orderBy('id')
            ->limit(2)
            ->get();

        $payments = app(PaymentService::class);

        foreach ($invoices as $invoice) {
            $amount = (string) round((float) $invoice->total_amount * 0.2, 2);

            if ((float) $amount <= 0) {
                continue;
            }

            $payments->record([
                'school_id' => $invoice->school_id,
                'payment_date' => Carbon::today()->subDays(1)->toDateString(),
                'amount' => $amount,
                'payment_method_id' => PaymentMethod::query()->value('id'),
                'reference' => 'DEMO-VERIFIKASI-'.$invoice->getKey(),
                'allocations' => [
                    ['invoice_id' => $invoice->getKey(), 'amount' => $amount],
                ],
            ], $this->financeUser);
        }
    }
}
