<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has many users', function () {
    $role = Role::factory()->superAdmin()->create();
    User::factory()->count(3)->create(['role_id' => $role->id]);

    expect($role->users)->toBeInstanceOf(Collection::class);
    expect($role->users)->toHaveCount(3);
});

it('can create a super admin role using factory', function () {
    $role = Role::factory()->superAdmin()->create();

    expect($role->name)->toBe('SuperAdmin');
});
