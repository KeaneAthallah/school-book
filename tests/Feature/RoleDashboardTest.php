<?php

use App\Dashboard\DashboardRole;
use App\Dashboard\DashboardRoleResolver;
use App\Dashboard\DashboardScope;
use App\Dashboard\Period;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Dashboard\DashboardMetrics;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function dashUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

/** @return array<string, string> */
function roleEmails(): array
{
    return [
        'admin@sekolah.test' => 'dashboard.admin',
        'operator@sekolah.test' => 'dashboard.operator',
        'gudang@sekolah.test' => 'dashboard.warehouse',
        'keuangan@sekolah.test' => 'dashboard.finance',
        'sales@sekolah.test' => 'dashboard.sales',
        'pimpinan@sekolah.test' => 'dashboard.executive',
    ];
}

it('renders a distinct dashboard for every supported role', function () {
    foreach (roleEmails() as $email => $view) {
        $this->actingAs(dashUser($email))
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs($view);
    }
});

it('renders every dashboard without any business records', function () {
    foreach (roleEmails() as $email => $view) {
        $this->actingAs(dashUser($email))
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs($view);
    }
});

it('redirects guests to the login screen', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('forbids accounts without the dashboard permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertForbidden();
});

it('renders a controlled page for a user with no recognised role', function () {
    $role = Role::factory()->create(['slug' => 'auditor']);
    $role->permissions()->attach(
        Permission::query()->where('slug', 'dashboard.view')->firstOrFail(),
    );

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.limited')
        ->assertSee('Akses Terbatas')
        // No operational data is exposed to an unrecognised role.
        ->assertDontSee('Total Pengguna')
        ->assertDontSee('Piutang');
});

it('resolves a multi-role user by the documented precedence', function () {
    $user = dashUser('sales@sekolah.test');
    $user->roles()->attach(Role::query()->where('slug', 'gudang')->firstOrFail());

    expect(app(DashboardRoleResolver::class)->resolve($user->refresh()))
        ->toBe(DashboardRole::Warehouse);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.warehouse');
});

it('keeps administrative access when a user also holds a narrower role', function () {
    $admin = dashUser('admin@sekolah.test');
    $admin->roles()->attach(Role::query()->where('slug', 'sales')->firstOrFail());

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.admin');
});

it('labels the school entry as the sales portfolio for sales users only', function () {
    $this->actingAs(dashUser('sales@sekolah.test'))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Sekolah Saya');
});

// ------------------------------------------------------------------- scoping

it('restricts the sales dashboard to the assigned portfolio', function () {
    $mine = dashUser('sales@sekolah.test');

    $other = User::factory()->create();
    $other->roles()->attach(Role::query()->where('slug', 'sales')->firstOrFail());

    $mySchool = School::factory()->create(['name' => 'Sekolah Binaan Saya', 'sales_user_id' => $mine->getKey()]);
    $otherSchool = School::factory()->create(['name' => 'Sekolah Milik PIC Lain', 'sales_user_id' => $other->getKey()]);

    Order::factory()->create(['school_id' => $mySchool->getKey()]);
    Order::factory()->create(['school_id' => $otherSchool->getKey()]);

    $this->actingAs($mine)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.sales')
        ->assertSee('Sekolah Binaan Saya')
        ->assertDontSee('Sekolah Milik PIC Lain');
});

it('restricts the sales order list to the assigned portfolio', function () {
    $mine = dashUser('sales@sekolah.test');

    $other = User::factory()->create();
    $other->roles()->attach(Role::query()->where('slug', 'sales')->firstOrFail());

    $otherSchool = School::factory()->create(['name' => 'Sekolah Milik PIC Lain', 'sales_user_id' => $other->getKey()]);
    Order::factory()->create(['school_id' => $otherSchool->getKey()]);

    $this->actingAs($mine)
        ->get('/orders')
        ->assertOk()
        ->assertDontSee('Sekolah Milik PIC Lain');
});

it('hides another portfolio from sales alerts', function () {
    $mine = dashUser('sales@sekolah.test');

    $other = User::factory()->create();
    $other->roles()->attach(Role::query()->where('slug', 'sales')->firstOrFail());

    $otherSchool = School::factory()->create(['sales_user_id' => $other->getKey()]);

    // An overdue invoice belonging to somebody else's school.
    Invoice::factory()->create([
        'school_id' => $otherSchool->getKey(),
        'status' => InvoiceStatus::Issued,
        'invoice_date' => now()->subDays(40)->toDateString(),
        'due_date' => now()->subDays(10)->toDateString(),
        'total_amount' => '750000.00',
        'paid_amount' => '0.00',
    ]);

    $scope = DashboardScope::forRole(DashboardRole::Sales, $mine);
    $metrics = app(DashboardMetrics::class);

    expect($metrics->overdueCount($scope))->toBe(0)
        ->and((float) $metrics->overdueTotal($scope))->toBe(0.0)
        // The unrestricted view still sees it, proving the fixture is real.
        ->and($metrics->overdueCount(DashboardScope::unrestricted()))->toBe(1);
});

// ------------------------------------------------------------- role limits

it('forbids warehouse users from financial operations', function () {
    $warehouse = dashUser('gudang@sekolah.test');

    expect($warehouse->hasPermission('payments.manage'))->toBeFalse()
        ->and($warehouse->hasPermission('invoices.manage'))->toBeFalse();

    $this->actingAs($warehouse)->post('/payments', [])->assertForbidden();
    $this->actingAs($warehouse)->post('/invoices', [])->assertForbidden();
    $this->actingAs($warehouse)->get('/receivables')->assertForbidden();
});

it('forbids finance users from inventory operations', function () {
    $finance = dashUser('keuangan@sekolah.test');

    expect($finance->hasPermission('procurement.manage'))->toBeFalse()
        ->and($finance->hasPermission('receipts.manage'))->toBeFalse()
        ->and($finance->hasPermission('fulfillment.pick'))->toBeFalse();

    $this->actingAs($finance)->post('/receipts', [])->assertForbidden();
    $this->actingAs($finance)->post('/pickings', [])->assertForbidden();
});

it('forbids the executive role from administrative writes', function () {
    $executive = dashUser('pimpinan@sekolah.test');

    expect($executive->hasPermission('users.manage'))->toBeFalse()
        ->and($executive->hasPermission('roles.manage'))->toBeFalse()
        ->and($executive->hasPermission('schools.manage'))->toBeFalse();

    $this->actingAs($executive)->post('/settings/users', [])->assertForbidden();
    $this->actingAs($executive)->put('/settings/roles/1', [])->assertForbidden();
    $this->actingAs($executive)->post('/master/schools', [])->assertForbidden();
});

it('enforces authorization on routes that are absent from the navigation', function () {
    $warehouse = dashUser('gudang@sekolah.test');

    // Rebate approval is neither permitted nor offered in the sidebar: the
    // server must still refuse it when the URL is requested directly.
    expect($warehouse->hasPermission('rebates.approve'))->toBeFalse();

    $this->actingAs($warehouse)
        ->get('/rebates')
        ->assertForbidden();
});

// ----------------------------------------------------------------- KPIs

it('reconciles receivable KPIs with invoice fixtures', function () {
    $school = School::factory()->create();

    // 1.000.000 total, 400.000 paid, not yet due.
    Invoice::factory()->create([
        'school_id' => $school->getKey(),
        'status' => InvoiceStatus::Issued,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(10)->toDateString(),
        'total_amount' => '1000000.00',
        'paid_amount' => '400000.00',
    ]);

    // 500.000 unpaid.
    Invoice::factory()->create([
        'school_id' => $school->getKey(),
        'status' => InvoiceStatus::Issued,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'total_amount' => '500000.00',
        'paid_amount' => '0.00',
    ]);

    $metrics = app(DashboardMetrics::class);
    $scope = DashboardScope::unrestricted();

    // 600.000 + 500.000 outstanding, nothing overdue yet.
    expect((float) $metrics->outstandingTotal($scope))->toBe(1100000.0)
        ->and($metrics->overdueCount($scope))->toBe(0)
        ->and((float) $metrics->overdueTotal($scope))->toBe(0.0);

    $buckets = $metrics->invoicePaymentBuckets($scope);

    expect($buckets['paid'])->toBe(0)
        ->and($buckets['partial'])->toBe(1)
        ->and($buckets['unpaid'])->toBe(1)
        ->and($buckets['overdue'])->toBe(0)
        // Buckets are mutually exclusive: every invoice lands in exactly one.
        ->and(array_sum($buckets))->toBe(2);
});

it('never counts a settled invoice as overdue debt', function () {
    $school = School::factory()->create();

    Invoice::factory()->create([
        'school_id' => $school->getKey(),
        'status' => InvoiceStatus::Issued,
        'invoice_date' => now()->subDays(40)->toDateString(),
        'due_date' => now()->subDays(10)->toDateString(),
        'total_amount' => '300000.00',
        'paid_amount' => '300000.00',
    ]);

    $metrics = app(DashboardMetrics::class);
    $scope = DashboardScope::unrestricted();

    expect($metrics->overdueCount($scope))->toBe(0)
        ->and((float) $metrics->overdueTotal($scope))->toBe(0.0)
        ->and((float) $metrics->outstandingTotal($scope))->toBe(0.0)
        ->and($metrics->invoicePaymentBuckets($scope)['paid'])->toBe(1);
});

it('counts only verified payments as received', function () {
    $school = School::factory()->create();

    Payment::factory()->create([
        'school_id' => $school->getKey(),
        'status' => PaymentStatus::Verified,
        'payment_date' => now()->toDateString(),
        'amount' => '250000.00',
    ]);

    Payment::factory()->create([
        'school_id' => $school->getKey(),
        'status' => PaymentStatus::Draft,
        'payment_date' => now()->toDateString(),
        'amount' => '999000.00',
    ]);

    $metrics = app(DashboardMetrics::class);

    expect((float) $metrics->paymentTotal(Period::make(), DashboardScope::unrestricted()))->toBe(250000.0)
        ->and($metrics->paymentsAwaitingVerification(DashboardScope::unrestricted()))->toBe(1);
});

it('applies the period to period-scoped metrics only', function () {
    $school = School::factory()->create();

    Order::factory()->create([
        'school_id' => $school->getKey(),
        'order_date' => now()->toDateString(),
        'status' => OrderStatus::Processing,
    ]);

    $metrics = app(DashboardMetrics::class);
    $scope = DashboardScope::unrestricted();

    expect($metrics->ordersInPeriod(Period::make(), $scope))->toBe(1)
        ->and($metrics->ordersInPeriod(Period::make('2020-01-01', '2020-01-31'), $scope))->toBe(0)
        // Point-in-time queues ignore the selected period.
        ->and($metrics->orderQueues($scope)['waiting_stock'])->toBe(1);
});
