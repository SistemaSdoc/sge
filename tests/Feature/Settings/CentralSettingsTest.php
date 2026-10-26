<?php

use App\Models\Central\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

function centralSettingsUser(): User
{
    $user = User::create([
        'nome' => 'Central User',
        'email' => 'central-settings@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $user->assignRole(Role::findOrCreate('SuperAdmin', 'web'));

    return $user;
}

test('central users can access profile settings', function () {
    $user = centralSettingsUser();

    $this->actingAs($user, 'web')
        ->get(route('central.dashboard.profile.edit'))
        ->assertOk();
});

test('central users update their central profile', function () {
    $user = centralSettingsUser();

    $this->actingAs($user, 'web')
        ->patch(route('central.dashboard.profile.update'), [
            'nome' => 'Updated Central User',
            'email' => 'updated-central-settings@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('central.dashboard.profile.edit'));

    expect($user->refresh()->nome)->toBe('Updated Central User')
        ->and($user->email)->toBe('updated-central-settings@example.com');
});
