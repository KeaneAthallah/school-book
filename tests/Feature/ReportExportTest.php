<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

it('streams a csv export of a report', function () {
    $admin = User::query()->where('email', 'admin@sekolah.test')->firstOrFail();

    $response = $this->actingAs($admin)->get('/reports/export?type=order_summary');

    $response->assertOk();
    expect($response->streamedContent())->toContain('No. Pesanan');
});

it('forbids a role without the report permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['slug' => 'tanpa_laporan']));

    $this->actingAs($user)->get('/reports')->assertForbidden();
});
