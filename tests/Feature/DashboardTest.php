<?php

use App\Dashboard\DashboardRole;
use App\Dashboard\Period;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function dashboardUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

it('renders the role dashboard with real KPI data', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard.admin')
        ->assertViewHas('role', fn ($role): bool => $role === DashboardRole::Admin)
        ->assertViewHas('data', function (array $data): bool {
            return isset(
                $data['kpis'],
                $data['sales_series']['labels'],
                $data['order_status'],
                $data['invoice_status'],
                $data['recent_orders'],
                $data['role_breakdown'],
                $data['stock_rows'],
                $data['recent_activity'],
                $data['notifications'],
            );
        });
});

it('shows the operational and financial KPI cards in order', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('data', function (array $data): bool {
            return collect($data['kpis'])->pluck('label')->all() === [
                'Pesanan Masuk',
                'Siap Picking',
                'Menunggu Stok',
                'Dalam Pengiriman',
                'Total Invoice',
                'Sudah Dibayar',
                'Piutang',
                'Jatuh Tempo',
            ];
        });
});

it('applies the requested date range to period metrics', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/dashboard?date_from=2026-03-01&date_to=2026-03-31')
        ->assertOk()
        ->assertViewHas('period', function (Period $period): bool {
            return $period->start->toDateString() === '2026-03-01'
                && $period->end->toDateString() === '2026-03-31'
                && $period->days() === 31;
        });
});

it('derives notifications from operational records', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->getJson('/notifications')
        ->assertOk()
        ->assertJsonStructure(['notifications']);
});

it('searches orders, schools, books and invoices', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->getJson('/search?q=SMP')
        ->assertOk()
        ->assertJsonStructure(['groups']);
});

it('returns nothing from search when the term is too short', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->getJson('/search?q=S')
        ->assertOk()
        ->assertJson(['groups' => []]);
});

it('returns no search results for a role without view permissions', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['slug' => 'tanpa_akses']));

    $this->actingAs($user)
        ->getJson('/search?q=SMP')
        ->assertOk()
        ->assertJson(['groups' => []]);
});

it('renders the receivables page', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/receivables')
        ->assertOk()
        ->assertViewIs('finance.receivables.index')
        ->assertViewHas('invoices')
        ->assertViewHas('summary')
        ->assertViewHas('aging');
});

it('renders the stock movement ledger', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/stock/movements')
        ->assertOk()
        ->assertViewIs('inventory.stock.movements')
        ->assertViewHas('movements')
        ->assertViewHas('types');
});

it('prints a delivery note for a shipment', function () {
    $shipment = Shipment::factory()->create();

    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/shipments/'.$shipment->id.'/print')
        ->assertOk()
        ->assertSee('SURAT JALAN');
});

it('filters the orders list by source', function () {
    $this->actingAs(dashboardUser('admin@sekolah.test'))
        ->get('/orders?source=SIPLAH')
        ->assertOk()
        ->assertViewIs('orders.index')
        ->assertViewHas('sources')
        ->assertViewHas('filters', fn (array $filters): bool => $filters['source'] === 'SIPLAH');
});
