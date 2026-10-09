<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function account(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

it('allows a role that holds the permission', function () {
    $this->actingAs(account('operator@sekolah.test'))
        ->get('/orders')
        ->assertOk();
});

it('forbids a role that does not hold the permission', function () {
    // The warehouse role has no access to user management.
    $this->actingAs(account('gudang@sekolah.test'))
        ->get('/settings/users')
        ->assertForbidden();
});

it('forbids a sales user from managing schools', function () {
    $this->actingAs(account('sales@sekolah.test'))
        ->post('/master/schools', ['name' => 'SMP Baru', 'status' => 'active'])
        ->assertForbidden();
});

it('lets a sales user view the schools list', function () {
    $this->actingAs(account('sales@sekolah.test'))
        ->get('/master/schools')
        ->assertOk();
});

it('grants the administrator every permission', function () {
    $permissions = account('admin@sekolah.test')->permissionSlugs();

    expect($permissions)->toContain('users.manage')
        ->and($permissions)->toContain('orders.confirm')
        ->and($permissions)->toContain('rebates.pay')
        ->and(count($permissions))->toBeGreaterThan(30);
});
