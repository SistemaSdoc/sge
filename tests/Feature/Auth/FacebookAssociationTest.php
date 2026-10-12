<?php

use App\Http\Controllers\Tenant\Settings\SecurityController;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function createFacebookAssociationTenantFixture(string $prefix, ?array $userAttributes = null): array
{
    $tenant = Tenant::create([
        'id' => $prefix.'-'.Str::uuid(),
        'status' => 'active',
    ]);
    $tenant->domains()->create(['domain' => 'sge.localhost']);
    $tenant->database()->manager()->createDatabase($tenant);

    $user = $tenant->run(function () use ($userAttributes): TenantUser {
        Schema::create('users', function ($table) {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        return TenantUser::create(array_merge([
            'nome' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('secret'),
            'facebook_id' => null,
        ], $userAttributes ?? []));
    });

    return ['tenant' => $tenant, 'user' => $user];
}

test('tenant user can start facebook association without exposing user identifiers in plain text', function () {
    ['tenant' => $tenant, 'user' => $user] = createFacebookAssociationTenantFixture('tenant-facebook-association');

    try {
        tenancy()->initialize($tenant);
        $this->be($user, 'tenant');

        $request = Request::create('http://sge.localhost/dashboard/settings/security/facebook/redirect', 'GET');
        $response = app(SecurityController::class)->beginFacebookLink($request);

        $location = parse_url($response->getTargetUrl());
        parse_str($location['query'] ?? '', $query);

        expect($query['tenant'] ?? null)->toBe('http://sge.localhost')
            ->and($query['link_state'] ?? null)->not->toBeNull()
            ->and(str_contains((string) $query['link_state'], 'user_id'))->toBeFalse();
    } finally {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $tenant->database()->manager()->deleteDatabase($tenant);
    }
});

test('facebook callback returns to the tenant security page under the dashboard route', function () {
    $response = $this->withSession([
        'facebook_tenant_login_url' => 'http://sge.localhost/',
        'facebook_link_state' => 'encrypted-link-state',
    ])->get('http://localhost/auth/facebook/callback?error=access_denied');

    $redirectUrl = parse_url($response->headers->get('Location'));
    parse_str($redirectUrl['query'] ?? '', $query);

    expect($redirectUrl['host'] ?? null)->toBe('sge.localhost')
        ->and($redirectUrl['path'] ?? null)->toBe('/dashboard/settings/security')
        ->and($query['facebook_link'] ?? null)->toBe('cancelled');
});

test('facebook callback returns to the exact user security page that initiated the association', function () {
    $response = $this->withSession([
        'facebook_tenant_login_url' => 'http://itcl.sge.localhost/',
        'facebook_link_state' => 'encrypted-link-state',
        'facebook_link_return_url' => '/dashboard/users/01a13026-624b-72a9-9cb4-2b670ce5d1b2/security',
    ])->get('http://localhost/auth/facebook/callback?error=access_denied');

    $redirectUrl = parse_url($response->headers->get('Location'));

    expect($redirectUrl['host'] ?? null)->toBe('itcl.sge.localhost')
        ->and($redirectUrl['path'] ?? null)->toBe('/dashboard/users/01a13026-624b-72a9-9cb4-2b670ce5d1b2/security');
});

test('facebook callback associates the provider id with the tenant user', function () {
    ['tenant' => $tenant, 'user' => $user] = createFacebookAssociationTenantFixture(
        'tenant-facebook-callback',
        ['avatar' => 'https://example.com/existing-avatar.jpg'],
    );

    try {
        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-123',
            'name' => 'John Doe',
            'avatar' => 'https://example.com/'.str_repeat('a', 300).'.jpg',
        ]));

        $state = Crypt::encryptString((string) json_encode([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'exp' => now()->addMinutes(10)->timestamp,
        ]));

        $response = $this->withSession([
            'facebook_link_state' => $state,
            'facebook_tenant_login_url' => 'http://sge.localhost/',
            'facebook_link_return_url' => '/dashboard/users/'.$user->getKey().'/security',
        ])->get('http://localhost/auth/facebook/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);

        $tenantUser = $tenant->run(fn () => $user->fresh());

        expect($tenantUser->facebook_id)->toBe('facebook-123')
            ->and($tenantUser->avatar)->toBe('https://example.com/existing-avatar.jpg')
            ->and($redirectUrl['path'] ?? null)->toBe('/dashboard/users/'.$user->getKey().'/security')
            ->and($query['facebook_link'] ?? null)->toBe('success');
    } finally {
        $tenant->database()->manager()->deleteDatabase($tenant);
    }
});

test('facebook callback preserves an existing link and returns an error result on conflict', function () {
    ['tenant' => $tenant, 'user' => $user] = createFacebookAssociationTenantFixture(
        'tenant-facebook-conflict',
        ['facebook_id' => 'facebook-existing'],
    );

    try {
        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-different',
            'name' => 'John Doe',
        ]));

        $state = Crypt::encryptString((string) json_encode([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'exp' => now()->addMinutes(10)->timestamp,
        ]));

        $response = $this->withSession([
            'facebook_link_state' => $state,
            'facebook_tenant_login_url' => 'http://sge.localhost/',
            'facebook_link_return_url' => '/dashboard/users/'.$user->getKey().'/security',
        ])->get('http://localhost/auth/facebook/callback');

        $redirectUrl = parse_url($response->headers->get('Location'));
        parse_str($redirectUrl['query'] ?? '', $query);

        expect($tenant->run(fn () => $user->fresh()->facebook_id))->toBe('facebook-existing')
            ->and($query['facebook_link'] ?? null)->toBe('error');
    } finally {
        $tenant->database()->manager()->deleteDatabase($tenant);
    }
});
