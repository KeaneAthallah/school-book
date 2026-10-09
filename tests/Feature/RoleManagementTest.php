<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function rbacAccount(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

function rbacRole(string $slug): Role
{
    return Role::query()->where('slug', $slug)->firstOrFail();
}

it('renders a permission checkbox for every editable role', function () {
    $response = $this->actingAs(rbacAccount('admin@sekolah.test'))->get('/settings/roles');

    $response->assertOk()
        ->assertSee('name="permissions[]"', false)
        ->assertSee('Pilih semua')
        ->assertSee('Daftar Peran')
        ->assertSee('x-data="permissionPicker(', false)
        ->assertSee('x-on:click="toggleAll(', false)
        ->assertSee('x-text="selected.length"', false);

    $editable = Role::query()->where('slug', '!=', 'admin')->count();

    expect(substr_count($response->getContent(), 'name="permissions[]"'))
        ->toBe($editable * Permission::query()->count());
});

it('saves the checked permissions for an editable role', function () {
    $sales = rbacRole('sales');

    $ids = Permission::query()
        ->whereIn('slug', ['orders.view', 'reports.view'])
        ->pluck('id')
        ->all();

    $this->actingAs(rbacAccount('admin@sekolah.test'))
        ->put('/settings/roles/'.$sales->getKey(), [
            'name' => 'Sales / PIC',
            'description' => $sales->description,
            'permissions' => $ids,
        ])
        ->assertRedirect();

    expect($sales->refresh()->permissions->pluck('slug')->sort()->values()->all())
        ->toBe(['orders.view', 'reports.view']);
});

it('can clear every permission from an editable role', function () {
    $sales = rbacRole('sales');

    expect($sales->permissions)->not->toBeEmpty();

    $this->actingAs(rbacAccount('admin@sekolah.test'))
        ->put('/settings/roles/'.$sales->getKey(), [
            'name' => $sales->name,
            'permissions' => [],
        ])
        ->assertRedirect();

    expect($sales->refresh()->permissions)->toBeEmpty();
});

it('refuses to change the administrator role', function () {
    $admin = rbacRole('admin');
    $before = $admin->permissions()->count();

    $this->actingAs(rbacAccount('admin@sekolah.test'))
        ->put('/settings/roles/'.$admin->getKey(), [
            'name' => 'Admin',
            'permissions' => [],
        ])
        ->assertForbidden();

    expect($admin->refresh()->permissions()->count())->toBe($before);
});

it('forbids accounts without the roles.manage permission', function () {
    $this->actingAs(rbacAccount('gudang@sekolah.test'))
        ->get('/settings/roles')
        ->assertForbidden();

    $this->actingAs(rbacAccount('keuangan@sekolah.test'))
        ->put('/settings/roles/'.rbacRole('sales')->getKey(), ['name' => 'Hacked', 'permissions' => []])
        ->assertForbidden();
});
