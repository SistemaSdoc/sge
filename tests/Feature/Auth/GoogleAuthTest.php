<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

test('user is redirected to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('user can login with google', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'google_id' => 'google-123',
    ]);

    $this->assertAuthenticatedAs(User::whereEmail('john@example.com')->first());
});

test('existing user can login with google', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'google_id' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]);
});

test('invalid state throws exception and redirects to login', function () {
    Socialite::fake('google');

    // This simulates an invalid state by sending a request with a mismatched state
    session(['state' => 'valid-state']);

    // Manually set an invalid state in the query
    $response = $this->get('/auth/google/callback?state=invalid-state&code=auth-code');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('toast', function ($toast) {
        return $toast['type'] === 'error' &&
            str_contains($toast['message'], 'autenticação');
    });
});

test('user with existing email can connect google', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'google_id' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Updated',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/new-avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]);
});
