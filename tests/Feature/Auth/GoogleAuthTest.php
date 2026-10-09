<?php

use App\Models\Central\Tenant;
use App\Models\Central\User;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('user is redirected to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('central.google.redirect'));

    $response->assertRedirect();
});

test('authenticated central user can start google authentication', function () {
    $user = User::create([
        'nome' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Socialite::fake('google');

    $response = $this->actingAs($user, 'web')
        ->get(route('central.google.redirect'));

    $response->assertRedirectContains('google');
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
    $tenant = Tenant::create([
        'id' => 'tenant-google-login',
        'status' => 'active',
    ]);
    $tenant->domains()->create([
        'domain' => 'sge.localhost',
    ]);

    $tenant->database()->manager()->createDatabase($tenant);

    $user = $tenant->run(function () {
        Schema::create('users', function ($table) {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        return TenantUser::create([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret'),
            'google_id' => null,
        ]);
    });

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    session(['google_tenant_domain' => 'http://sge.localhost']);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]);
});

test('google callback redirects to the tenant token route using impersonation', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-callback',
        'status' => 'active',
    ]);
    $tenant->domains()->create([
        'domain' => 'sge.localhost',
    ]);

    $tenant->database()->manager()->createDatabase($tenant);

    $tenant->run(function () {
        Schema::create('users', function ($table) {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        TenantUser::create([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret'),
            'google_id' => null,
        ]);
    });

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    session(['google_tenant_domain' => 'sge.localhost']);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
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
    $tenant = Tenant::create([
        'id' => 'tenant-google-connect',
        'status' => 'active',
    ]);
    $tenant->domains()->create([
        'domain' => 'sge.localhost',
    ]);

    $tenant->database()->manager()->createDatabase($tenant);

    $user = $tenant->run(function () {
        Schema::create('users', function ($table) {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        return TenantUser::create([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret'),
            'google_id' => null,
        ]);
    });

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Updated',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/new-avatar.jpg',
    ]));

    session(['google_tenant_domain' => 'http://sge.localhost']);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]);
});
