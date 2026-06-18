<?php

use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('master users can access the dashboard using the master role', function () {
    $role = Role::firstOrCreate(['nome' => 'Master']);
    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});