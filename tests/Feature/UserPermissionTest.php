<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

it('grants a permission assigned to one of the user roles', function () {
    $permission = Permission::factory()->create(['slug' => 'orders.view']);
    $role = Role::factory()->create(['slug' => 'operator_pesanan']);
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    expect($user->hasPermission('orders.view'))->toBeTrue()
        ->and($user->hasPermission('orders.delete'))->toBeFalse();
});

it('reports no roles or permissions for a user without roles', function () {
    $user = User::factory()->create();

    expect($user->hasAnyRole(['admin']))->toBeFalse()
        ->and($user->permissionSlugs())->toBe([])
        ->and($user->isAdmin())->toBeFalse();
});

it('treats the admin role as administrator', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['slug' => 'admin']));

    expect($user->isAdmin())->toBeTrue();
});
