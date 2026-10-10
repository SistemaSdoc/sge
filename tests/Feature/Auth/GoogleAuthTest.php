<?php

use App\Models\Central\Tenant;
use App\Models\Central\User;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * @return array{tenant: Tenant, user: TenantUser|null}
 */
function createGoogleAuthTenantFixture(string $prefix, ?array $userAttributes = null, bool $createUsersTable = true): array
{
    $tenant = Tenant::create([
        'id' => $prefix.'-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);
    $tenant->database()->manager()->createDatabase($tenant);

    $user = $tenant->run(function () use ($createUsersTable, $userAttributes): ?TenantUser {
        if (! $createUsersTable) {
            return null;
        }

        Schema::create('users', function ($table) {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        if ($userAttributes === null) {
            return null;
        }

        return TenantUser::create(array_merge([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret'),
            'google_id' => null,
        ], $userAttributes));
    });

    return ['tenant' => $tenant, 'user' => $user];
}

function deleteGoogleAuthTenantFixture(Tenant $tenant): void
{
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    $tenant->database()->manager()->deleteDatabase($tenant);
}

test('user is redirected to google', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-redirect-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);

    Socialite::fake('google');

    $response = $this->get(route('central.google.redirect', [
        'tenant' => 'http://sge.localhost:8001',
    ]));

    $response->assertRedirect();
    $this->assertSame($tenant->getKey(), session('google_tenant_id'));
    $this->assertSame('http://sge.localhost:8001/', session('google_tenant_login_url'));
});

test('authenticated central user can start google authentication', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-authenticated-redirect-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);

    $user = User::create([
        'nome' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Socialite::fake('google');

    $response = $this->actingAs($user, 'web')
        ->get(route('central.google.redirect', [
            'tenant' => 'http://sge.localhost',
        ]));

    $response->assertRedirectContains('google');
});

test('google redirect rejects an unregistered tenant domain', function () {
    Socialite::fake('google');

    $this->get(route('central.google.redirect', [
        'tenant' => 'http://unregistered.localhost',
    ]))->assertNotFound();
});

test('google redirect rejects a suspended tenant', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-redirect-suspended-'.Str::uuid(),
        'status' => 'suspended',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);

    Socialite::fake('google');

    $this->get(route('central.google.redirect', [
        'tenant' => 'http://sge.localhost',
    ]))->assertNotFound();
});

test('google redirect rejects a malformed tenant origin', function () {
    Socialite::fake('google');

    $this->get(route('central.google.redirect', [
        'tenant' => 'javascript://attacker.example',
    ]))->assertBadRequest();
});

test('tenant login maps known google errors and ignores arbitrary values', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-alert-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'tenant-google-alert.localhost']);
    $tenant->database()->manager()->createDatabase($tenant);

    $tenant->run(function () {
        Schema::create('instituicoes', function ($table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
    });

    try {
        $accountErrorPath = URL::temporarySignedRoute(
            'tenant.google.error',
            now()->addMinutes(5),
            ['google_error' => 'account'],
            absolute: false,
        );

        $this->followingRedirects()
            ->get('http://tenant-google-alert.localhost'.$accountErrorPath)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('tenant/auth/login')
                ->where('googleError', 'Não foi possível autenticar com esta conta Google. Confirme se o email está verificado e associado a um utilizador ativo desta instituição.'));

        $tamperedErrorPath = str_replace('google_error=account', 'google_error=failed', $accountErrorPath);

        $this->get('http://tenant-google-alert.localhost'.$tamperedErrorPath)
            ->assertForbidden();

        $expiredErrorPath = URL::temporarySignedRoute(
            'tenant.google.error',
            now()->subMinute(),
            ['google_error' => 'account'],
            absolute: false,
        );

        $this->get('http://tenant-google-alert.localhost'.$expiredErrorPath)
            ->assertForbidden();

        $unknownErrorPath = URL::temporarySignedRoute(
            'tenant.google.error',
            now()->addMinutes(5),
            ['google_error' => 'unknown'],
            absolute: false,
        );

        $this->get('http://tenant-google-alert.localhost'.$unknownErrorPath)
            ->assertBadRequest();

        $deniedErrorPath = URL::temporarySignedRoute(
            'tenant.google.error',
            now()->addMinutes(5),
            ['google_error' => 'denied'],
            absolute: false,
        );

        $this->followingRedirects()
            ->get('http://tenant-google-alert.localhost'.$deniedErrorPath)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('tenant/auth/login')
                ->where('googleError', 'A autenticação com Google foi cancelada. Pode tentar novamente.'));

        foreach ([
            'state' => 'A autenticação com Google expirou ou não pôde ser validada. Tente novamente.',
            'failed' => 'Não foi possível concluir a autenticação com Google. Tente novamente.',
        ] as $errorCode => $message) {
            $errorPath = URL::temporarySignedRoute(
                'tenant.google.error',
                now()->addMinutes(5),
                ['google_error' => $errorCode],
                absolute: false,
            );

            $this->followingRedirects()
                ->get('http://tenant-google-alert.localhost'.$errorPath)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('tenant/auth/login')
                    ->where('googleError', $message));
        }
    } finally {
        tenancy()->end();
        $tenant->database()->manager()->deleteDatabase($tenant);
    }
});

test('unregistered google email is rejected without creating a user', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-unregistered-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);
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
    });

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
        'email_verified' => true,
    ]));

    $response = $this->withSession([
        'google_tenant_id' => $tenant->getKey(),
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback');

    $redirectUrl = parse_url($response->headers->get('Location'));
    parse_str($redirectUrl['query'] ?? '', $query);
    expect($redirectUrl['host'] ?? null)->toBe('sge.localhost')
        ->and($redirectUrl['path'] ?? null)->toBe('/auth/google/error')
        ->and($query['google_error'] ?? null)->toBe('account')
        ->and(isset($query['signature']))->toBeTrue();
    $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    $this->assertGuest();
    $tenant->database()->manager()->deleteDatabase($tenant);
});

test('google email that is not verified is rejected', function () {
    ['tenant' => $tenant] = createGoogleAuthTenantFixture('tenant-google-unverified');

    try {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'john@example.com',
            'email_verified' => false,
        ]));

        $response = $this->withSession([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/google/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);
        expect($query['google_error'] ?? null)->toBe('account')
            ->and(isset($query['signature']))->toBeTrue();

        $tenant->run(fn () => $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]));
    } finally {
        deleteGoogleAuthTenantFixture($tenant);
    }
});

test('google login is rejected when its identity is linked to another google account', function () {
    ['tenant' => $tenant, 'user' => $user] = createGoogleAuthTenantFixture(
        'tenant-google-conflict',
        ['google_id' => 'different-google-id'],
    );

    try {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'john@example.com',
            'email_verified' => true,
        ]));

        $response = $this->withSession([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/google/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);
        expect($query['google_error'] ?? null)->toBe('account');

        expect($tenant->run(fn () => $user->fresh()->google_id))->toBe('different-google-id');
    } finally {
        deleteGoogleAuthTenantFixture($tenant);
    }
});

test('suspended tenant cannot complete google login', function () {
    ['tenant' => $tenant] = createGoogleAuthTenantFixture('tenant-google-suspended');
    $tenant->update(['status' => 'suspended']);

    try {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'john@example.com',
            'email_verified' => true,
        ]));

        $response = $this->withSession([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/google/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);
        expect($query['google_error'] ?? null)->toBe('account');
    } finally {
        deleteGoogleAuthTenantFixture($tenant);
    }
});

test('google callback without tenant context returns to central login', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'email' => 'john@example.com',
        'email_verified' => true,
    ]));

    $this->get('http://localhost/auth/google/callback')
        ->assertRedirect(route('central.login'));
});

test('google consent denial returns a signed cancellation message', function () {
    $response = $this->withSession([
        'google_tenant_id' => 'tenant-id',
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback?error=access_denied');

    $redirectUrl = parse_url($response->headers->get('Location'));
    parse_str($redirectUrl['query'] ?? '', $query);
    expect($query['google_error'] ?? null)->toBe('denied')
        ->and(isset($query['signature']))->toBeTrue();
});

test('google callback with an unknown tenant returns an account error', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'email' => 'john@example.com',
        'email_verified' => true,
    ]));

    $response = $this->withSession([
        'google_tenant_id' => 'missing-tenant',
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback');

    $redirectUrl = parse_url($response->headers->get('Location'));
    parse_str($redirectUrl['query'] ?? '', $query);
    expect($query['google_error'] ?? null)->toBe('account')
        ->and(isset($query['signature']))->toBeTrue();
});

test('google callback without a tenant return address falls back to central login', function () {
    ['tenant' => $tenant] = createGoogleAuthTenantFixture('tenant-google-missing-origin');

    try {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'john@example.com',
            'email_verified' => true,
        ]));

        $this->withSession([
            'google_tenant_id' => $tenant->getKey(),
        ])->get('http://localhost/auth/google/callback')
            ->assertRedirect(route('central.login'));
    } finally {
        deleteGoogleAuthTenantFixture($tenant);
    }
});

test('unexpected tenant database errors return a generic signed failure', function () {
    ['tenant' => $tenant] = createGoogleAuthTenantFixture(
        'tenant-google-failure',
        createUsersTable: false,
    );

    try {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'john@example.com',
            'email_verified' => true,
        ]));

        $response = $this->withSession([
            'google_tenant_id' => $tenant->getKey(),
            'google_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/google/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);
        expect($query['google_error'] ?? null)->toBe('failed')
            ->and(isset($query['signature']))->toBeTrue()
            ->and(tenancy()->initialized)->toBeFalse();
    } finally {
        deleteGoogleAuthTenantFixture($tenant);
    }
});

test('existing user can login with google', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-login-'.Str::uuid(),
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

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
        'email_verified' => true,
    ]));

    $response = $this->withSession([
        'google_tenant_id' => $tenant->getKey(),
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback');

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
    $tenant->run(fn () => $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]));
    $tenant->database()->manager()->deleteDatabase($tenant);
});

test('google callback redirects to the tenant token route using impersonation', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-callback-'.Str::uuid(),
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

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
        'email_verified' => true,
    ]));

    $response = $this->withSession([
        'google_tenant_id' => $tenant->getKey(),
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback');

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
    $tenant->database()->manager()->deleteDatabase($tenant);
});

test('invalid state returns to tenant login with a safe error code', function () {
    Socialite::fake('google');

    // This simulates an invalid state by sending a request with a mismatched state
    session(['state' => 'valid-state']);

    // Manually set an invalid state in the query
    $response = $this->withSession([
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('/auth/google/callback?state=invalid-state&code=auth-code');

    $redirectUrl = parse_url($response->headers->get('Location'));
    parse_str($redirectUrl['query'] ?? '', $query);
    expect($redirectUrl['host'] ?? null)->toBe('sge.localhost')
        ->and($redirectUrl['path'] ?? null)->toBe('/auth/google/error')
        ->and($query['google_error'] ?? null)->toBe('state')
        ->and(isset($query['signature']))->toBeTrue();
});

test('user with existing email can connect google', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-google-connect-'.Str::uuid(),
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

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'John Updated',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/new-avatar.jpg',
        'email_verified' => true,
    ]));

    $response = $this->withSession([
        'google_tenant_id' => $tenant->getKey(),
        'google_tenant_login_url' => 'http://sge.localhost/',
    ])->get('http://localhost/auth/google/callback');

    $response->assertRedirectContains('sge.localhost/token/');
    $this->assertGuest();
    $tenant->run(fn () => $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]));
    $tenant->database()->manager()->deleteDatabase($tenant);
});
