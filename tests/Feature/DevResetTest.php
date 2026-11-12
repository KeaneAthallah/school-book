<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function devResetAccount(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

it('requires authentication to view dev reset', function () {
    $this->get('/dev/reset')->assertRedirect(route('login'));
});

it('forbids non-admin users from viewing dev reset', function () {
    $this->actingAs(devResetAccount('gudang@sekolah.test'))
        ->get('/dev/reset')
        ->assertForbidden();
});

it('allows admin to view dev reset', function () {
    $this->actingAs(devResetAccount('admin@sekolah.test'))
        ->get('/dev/reset')
        ->assertOk();
});

it('requires confirmation to reset', function () {
    $this->actingAs(devResetAccount('admin@sekolah.test'))
        ->post('/dev/reset', [])
        ->assertSessionHasErrors('confirmation');
});

it('rejects wrong confirmation', function () {
    $this->actingAs(devResetAccount('admin@sekolah.test'))
        ->post('/dev/reset', ['confirmation' => 'nope'])
        ->assertSessionHasErrors('confirmation');
});