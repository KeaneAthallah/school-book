<?php

use App\Models\User;

it('shows the login page to guests', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewIs('auth.login');
});

it('redirects guests from the dashboard to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('authenticates a user with valid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->from('/login')
        ->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
