<?php

use App\Dashboard\DashboardScope;
use App\Dashboard\Period;
use App\Models\Book;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\Stock;
use App\Models\User;
use App\Services\Dashboard\DashboardMetrics;
use Database\Seeders\DashboardDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/**
 * The demo seeder must be safe and repeatable: guarded against production,
 * idempotent, and consistent with the application's own business rules.
 */
function seedReferenceData(): void
{
    test()->seed(DatabaseSeeder::class);
}

it('refuses to run in the production environment', function () {
    seedReferenceData();

    $this->app->detectEnvironment(fn (): string => 'production');

    $seeder = new DashboardDemoSeeder;
    $seeder->setContainer($this->app);

    expect(fn () => $seeder->run())->toThrow(RuntimeException::class);
});

it('does not duplicate demo records when run twice', function () {
    seedReferenceData();

    $this->seed(DashboardDemoSeeder::class);

    $snapshot = [
        'orders' => Order::query()->count(),
        'invoices' => Invoice::query()->count(),
        'books' => Book::query()->count(),
        'schools' => School::query()->count(),
        'stocks' => Stock::query()->count(),
    ];

    $this->seed(DashboardDemoSeeder::class);

    expect(Order::query()->count())->toBe($snapshot['orders'])
        ->and(Invoice::query()->count())->toBe($snapshot['invoices'])
        ->and(Book::query()->count())->toBe($snapshot['books'])
        ->and(School::query()->count())->toBe($snapshot['schools'])
        ->and(Stock::query()->count())->toBe($snapshot['stocks']);
});

it('preserves existing accounts and their passwords', function () {
    seedReferenceData();

    $admin = User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
    $originalHash = $admin->password;
    $originalCount = User::query()->count();

    $this->seed(DashboardDemoSeeder::class);

    $admin->refresh();

    expect(User::query()->count())->toBe($originalCount)
        ->and($admin->password)->toBe($originalHash);
});

it('seeds inventory, orders and finances that reconcile with the business rules', function () {
    seedReferenceData();
    $this->seed(DashboardDemoSeeder::class);

    expect(Order::query()->count())->toBeGreaterThan(30)
        ->and(Invoice::query()->count())->toBeGreaterThan(5)
        ->and(Stock::query()->count())->toBeGreaterThan(0);

    // Stock was created through the service layer, so no balance is negative.
    expect(Stock::query()->where('quantity', '<', 0)->count())->toBe(0)
        ->and(Stock::query()->where('reserved_quantity', '<', 0)->count())->toBe(0);

    // The reservation balance matches the reservations held by order lines.
    expect((int) Stock::query()->sum('reserved_quantity'))
        ->toBe((int) OrderItem::query()->sum('reserved_quantity'));

    // Nothing was shipped beyond what was ordered or reserved.
    expect(OrderItem::query()->whereColumn('shipped_quantity', '>', 'quantity')->count())->toBe(0);

    // Invoice totals agree with their own line items.
    foreach (Invoice::query()->with('items')->get() as $invoice) {
        $expected = (float) $invoice->items->sum('subtotal')
            + (float) $invoice->tax_amount
            - (float) $invoice->discount_amount;

        expect(round((float) $invoice->total_amount, 2))->toBe(round($expected, 2));
    }

    // paid_amount always equals the sum of verified allocations.
    foreach (Invoice::query()->get() as $invoice) {
        $verified = PaymentAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', 'verified'))
            ->sum('amount');

        expect(round((float) $invoice->paid_amount, 2))->toBe(round((float) $verified, 2));
    }
});

it('never dates demo records in the future', function () {
    seedReferenceData();
    $this->seed(DashboardDemoSeeder::class);

    $today = now()->toDateString();

    expect(DB::table('orders')->whereDate('order_date', '>', $today)->count())->toBe(0)
        ->and(DB::table('invoices')->whereDate('invoice_date', '>', $today)->count())->toBe(0)
        ->and(DB::table('payments')->whereDate('payment_date', '>', $today)->count())->toBe(0)
        ->and(DB::table('shipments')->whereDate('ship_date', '>', $today)->count())->toBe(0);
});

it('produces meaningful dashboard figures from persisted records', function () {
    seedReferenceData();
    $this->seed(DashboardDemoSeeder::class);

    $metrics = app(DashboardMetrics::class);
    $period = Period::make();
    $scope = DashboardScope::unrestricted();

    // Current-month KPIs are populated from real rows.
    expect($metrics->ordersInPeriod($period, $scope))->toBeGreaterThan(0)
        ->and((float) $metrics->paymentTotal($period, $scope))->toBeGreaterThan(0)
        ->and((float) $metrics->outstandingTotal($scope))->toBeGreaterThan(0);

    // Point-in-time widget data is present.
    expect(count($metrics->recentOrders($scope)))->toBeGreaterThan(0)
        ->and(count($metrics->stockRows()))->toBeGreaterThan(0)
        ->and(collect($metrics->orderStatusDistribution($scope))->sum('value'))->toBeGreaterThan(0)
        ->and(collect($metrics->invoiceStatusDistribution($scope))->sum('value'))->toBeGreaterThan(0)
        ->and(count($metrics->recentActivity()))->toBeGreaterThan(0);
});

it('shows the seeded data on the dashboard page for an administrator', function () {
    seedReferenceData();
    $this->seed(DashboardDemoSeeder::class);

    $admin = User::query()->where('email', 'admin@sekolah.test')->firstOrFail();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.admin')
        ->assertViewHas('data', fn (array $data): bool => count($data['notifications']) > 0);
});
