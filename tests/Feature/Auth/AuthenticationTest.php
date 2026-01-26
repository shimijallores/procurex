<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays the login page for guests', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Login'));
});

it('allows a user to login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->post(route('login.login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard.index'));
    $this->assertAuthenticatedAs($user);
});

it('prevents login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'correct-password',
    ]);

    $response = $this->post(route('login.login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('content');
    $this->assertGuest();
});

it('allows an authenticated user to logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('redirects authenticated users away from login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('dashboard.index'));
});

it('requires email and password for login', function () {
    $response = $this->post(route('login.login'), []);

    $response->assertSessionHasErrors(['email', 'password']);
});

it('requires a valid email format for login', function () {
    $response = $this->post(route('login.login'), [
        'email' => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});
