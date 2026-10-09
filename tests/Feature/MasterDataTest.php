<?php

use App\Models\School;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function masterAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

it('lists master data for an administrator', function () {
    School::factory()->count(3)->create();

    $this->actingAs(masterAdmin())
        ->get('/master/schools')
        ->assertOk();
});

it('filters master data by search term', function () {
    School::factory()->create(['name' => 'SMP Khusus Pencarian']);
    School::factory()->create(['name' => 'SMP Lain']);

    $this->actingAs(masterAdmin())
        ->get('/master/schools?search=Khusus')
        ->assertOk();
});

it('creates a master data record', function () {
    $this->actingAs(masterAdmin())
        ->post('/master/schools', [
            'name' => 'SMP Negeri Baru',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('schools', ['name' => 'SMP Negeri Baru']);
});

it('validates required fields when creating', function () {
    $this->actingAs(masterAdmin())
        ->post('/master/schools', ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('updates a master data record', function () {
    $school = School::factory()->create(['name' => 'Nama Lama']);

    $this->actingAs(masterAdmin())
        ->put('/master/schools/'.$school->id, [
            'name' => 'Nama Baru',
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect($school->refresh()->name)->toBe('Nama Baru')
        ->and($school->status)->toBe('inactive');
});

it('deletes a master data record', function () {
    $school = School::factory()->create();

    $this->actingAs(masterAdmin())
        ->delete('/master/schools/'.$school->id)
        ->assertRedirect();

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
});

it('returns not found for an unknown master data resource', function () {
    $this->actingAs(masterAdmin())
        ->get('/master/does-not-exist')
        ->assertNotFound();
});
