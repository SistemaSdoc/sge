<?php

use App\Models\Central\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('user is redirected to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('unregistered google email is rejected without creating a user', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('tenant.login'));
    $response->assertSessionHas('toast.message', 'Não foi possível iniciar sessão com esta conta Google. Confirme que o seu email já está cadastrado e tente novamente.');
    $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    $this->assertGuest();
});

test('existing user can login with google', function () {
    $user = User::create([
        'nome' => 'John Doe',
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

    $response->assertRedirect(route('tenant.login'));
    $response->assertSessionHas('toast', function ($toast) {
        return $toast['type'] === 'error' &&
            str_contains($toast['message'], 'autenticação');
    });
});

test('user with existing email can connect google', function () {
    $user = User::create([
        'nome' => 'John Doe',
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
