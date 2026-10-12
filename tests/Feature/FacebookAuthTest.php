<?php

use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * @return array{tenant: Tenant, user: TenantUser}
 */
function createFacebookLoginTenantFixture(string $prefix, array $userAttributes = []): array
{
    $tenant = Tenant::create([
        'id' => $prefix.'-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);
    $tenant->database()->manager()->createDatabase($tenant);

    $user = $tenant->run(function () use ($userAttributes): TenantUser {
        Schema::create('users', function ($table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        return TenantUser::create(array_merge([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret'),
            'facebook_id' => 'facebook-123',
        ], $userAttributes));
    });

    return ['tenant' => $tenant, 'user' => $user];
}

function deleteFacebookLoginTenantFixture(Tenant $tenant): void
{
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    $tenant->database()->manager()->deleteDatabase($tenant);
}

test('facebook login redirect stores the validated tenant context', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-facebook-login-redirect-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);

    Socialite::fake('facebook');

    $this->get('http://localhost/auth/facebook/redirect?tenant=http%3A%2F%2Fsge.localhost')
        ->assertRedirect();

    expect(session('facebook_tenant_id'))->toBe($tenant->getKey())
        ->and(session('facebook_tenant_login_url'))->toBe('http://sge.localhost/');
});

test('facebook login finds a linked user by provider id without requiring email', function () {
    ['tenant' => $tenant, 'user' => $user] = createFacebookLoginTenantFixture('tenant-facebook-login');

    try {
        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-123',
            'name' => 'John Doe',
            'email' => null,
        ]));

        $response = $this->withSession([
            'facebook_tenant_id' => $tenant->getKey(),
            'facebook_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/facebook/callback');

        $response->assertRedirectContains('sge.localhost/token/');
        $tenant->run(fn () => $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'facebook_id' => 'facebook-123',
        ]));
    } finally {
        deleteFacebookLoginTenantFixture($tenant);
    }
});

test('facebook login rejects an identity that is not linked to a tenant user', function () {
    ['tenant' => $tenant] = createFacebookLoginTenantFixture('tenant-facebook-unlinked');

    try {
        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-unlinked',
            'email' => null,
        ]));

        $response = $this->withSession([
            'facebook_tenant_id' => $tenant->getKey(),
            'facebook_tenant_login_url' => 'http://sge.localhost/',
        ])->get('http://localhost/auth/facebook/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);

        expect($redirectUrl['path'] ?? null)->toBe('/auth/facebook/error')
            ->and($query['facebook_error'] ?? null)->toBe('account')
            ->and(isset($query['signature']))->toBeTrue();
    } finally {
        deleteFacebookLoginTenantFixture($tenant);
    }
});

test('signed Facebook login errors are shown on the tenant login page', function () {
    $tenant = Tenant::create([
        'id' => 'tenant-facebook-error-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'tenant-facebook-error.localhost']);
    $tenant->database()->manager()->createDatabase($tenant);

    $tenant->run(function (): void {
        Schema::create('instituicoes', function ($table): void {
            $table->string('id')->primary();
            $table->softDeletes();
        });
    });

    try {
        $signedPath = URL::temporarySignedRoute(
            'tenant.facebook.error',
            now()->addMinutes(5),
            ['facebook_error' => 'denied'],
            absolute: false,
        );

        $this->followingRedirects()
            ->get('http://tenant-facebook-error.localhost'.$signedPath)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('tenant/auth/login')
                ->where('facebookError', 'A autenticação com Facebook foi cancelada. Pode tentar novamente.'));
    } finally {
        deleteFacebookLoginTenantFixture($tenant);
    }
});
