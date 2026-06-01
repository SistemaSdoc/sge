<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('password-confirmation-google.callback'));

    $response->assertRedirect(route('login'));
});

test('user is redirected to google during password confirmation', function () {
    $user = User::factory()->create();

    Socialite::fake('google');

    $response = $this
        ->actingAs($user)
        ->get(route('password-confirmation-google.redirect'));

    $response->assertRedirect();
});

test('user can confirm password with google', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this
        ->actingAs($user)
        ->session(['url.intended' => '/settings/security'])
        ->get(route('password-confirmation-google.callback'));

    $response->assertRedirect('/settings/security');
});

test('google email mismatch redirects to confirm password', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this
        ->actingAs($user)
        ->get(route('password-confirmation-google.callback'));

    $response->assertRedirect(route('password.confirm'));
    $response->assertSessionHas('toast', function ($toast) {
        return $toast['type'] === 'error' &&
            str_contains($toast['message'], 'email');
    });
});

test('invalid google state redirects with error', function () {
    $user = User::factory()->create();

    Socialite::fake('google');

    // Simulate invalid state
    session(['state' => 'valid-state']);

    $response = $this
        ->actingAs($user)
        ->get('/password-confirmation/google/callback?state=invalid-state&code=auth-code');

    $response->assertRedirect(route('password.confirm'));
    $response->assertSessionHas('toast', function ($toast) {
        return $toast['type'] === 'error' &&
            str_contains($toast['message'], 'autenticação');
    });
});
