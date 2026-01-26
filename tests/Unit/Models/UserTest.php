<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a role', function (): void {
    $role = Role::factory()->superAdmin()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    expect($user->role)->toBeInstanceOf(Role::class);
    expect($user->role->id)->toBe($role->id);
});

it('hashes the password automatically', function (): void {
    $user = User::factory()->create(['password' => 'plain-password']);

    expect($user->password)->not->toBe('plain-password');
    expect(password_verify('plain-password', $user->password))->toBeTrue();
});

it('hides sensitive attributes when serialized', function (): void {
    $user = User::factory()->create();
    $serialized = $user->toArray();

    expect($serialized)->not->toHaveKey('password');
    expect($serialized)->not->toHaveKey('remember_token');
});
