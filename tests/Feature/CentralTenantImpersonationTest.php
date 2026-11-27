<?php

use App\Models\Central\Tenant;
use App\Models\Central\User as CentralUser;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User as TenantUser;
use App\Services\Central\TenantMetricsService;
use Database\Seeders\Tenant\CentralSupportUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    tenancy()->end();

    if (isset($this->tenant)) {
        DB::purge('tenant');
        File::delete(database_path($this->tenant->database()->getName()));
    }
});

it('lets a central superadmin enter the tenant as the support account through a one-time token', function (): void {
    $this->tenant = Tenant::create([
        'id' => 'tenant-impersonation-test-'.Str::uuid7(),
        'status' => 'active',
        'admin_user_id' => 'tenant-admin-id',
    ]);

    DB::purge('tenant');
    File::delete(database_path($this->tenant->database()->getName()));
    File::put(database_path($this->tenant->database()->getName()), '');
    $this->tenant->run(function (): void {
        Schema::connection('tenant')->create('users', function ($table): void {
            $table->string('id')->primary();
            $table->string('instituicao_id')->nullable();
            $table->string('nome');
            $table->string('email');
            $table->string('password')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::connection('tenant')->create('instituicoes', function ($table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('sigla')->nullable();
            $table->string('tipo')->nullable();
            $table->string('status')->nullable();
            $table->string('tenant_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::connection('tenant')->create('roles', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::connection('tenant')->create('permissions', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::connection('tenant')->create('model_has_roles', function ($table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->string('model_id');
        });
        Schema::connection('tenant')->create('role_has_permissions', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        DB::connection('tenant')->table('instituicoes')->insert([
            'id' => 'tenant-institution-id',
            'nome' => 'Instituição Tenant',
        ]);

        DB::connection('tenant')->table('users')->insert([
            [
                'id' => 'tenant-admin-id',
                'nome' => 'Director Tenant',
                'email' => 'director@tenant.test',
            ],
            [
                'id' => 'existing-tenant-user-id',
                'nome' => 'Existing Tenant User',
                'email' => 'user@tenant.test',
            ],
        ]);
    });
    $this->tenant->domains()->create(['domain' => 'impersonation.test']);

    $supportUserId = $this->tenant->run(function (): string {
        (new CentralSupportUserSeeder)->run();

        $supportUser = TenantUser::query()
            ->where('email', TenantUser::CENTRAL_SUPPORT_EMAIL)
            ->firstOrFail();

        expect($supportUser->isSuperAdmin())->toBeTrue()
            ->and($supportUser->isDirector())->toBeTrue()
            ->and($supportUser->instituicao_id)->toBe('tenant-institution-id')
            ->and(Gate::forUser($supportUser)->allows('viewAny', Instituicao::class))->toBeTrue()
            ->and(TenantUser::query()->visibleInTenantUserLists()->pluck('id')->all())
            ->toContain('existing-tenant-user-id')
            ->not->toContain($supportUser->getKey())
            ->and(TenantUser::query()
                ->where('email', TenantUser::CENTRAL_SUPPORT_EMAIL)
                ->count())->toBe(1);

        return (string) $supportUser->getKey();
    });

    $userMetrics = $this->tenant->run(function (): array {
        $supportUser = TenantUser::query()
            ->where('email', TenantUser::CENTRAL_SUPPORT_EMAIL)
            ->firstOrFail();
        $supportUser->forceFill(['last_login_at' => now()])->save();

        $director = TenantUser::query()->findOrFail('tenant-admin-id');
        $director->forceFill(['last_login_at' => now()->subDay()])->save();
        $director->assignRole(Role::findOrCreate('Director', 'tenant'));

        return app(TenantMetricsService::class)->getUserMetrics();
    });

    expect($userMetrics['users'])
        ->toMatchArray([
            'total' => 2,
            'ativos' => 1,
            'inactivos' => 1,
            'diretores' => 1,
        ])
        ->and(collect($userMetrics['logins_semana'])->sum('logins'))->toBe(1);

    $centralAdmin = CentralUser::factory()->create([
        'email_verified_at' => now(),
    ]);
    $centralAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $existingTenantUser = $this->tenant->run(
        fn (): TenantUser => TenantUser::query()->findOrFail('existing-tenant-user-id'),
    );

    $response = $this->withHeaders(['X-Inertia' => 'true'])
        ->actingAs($centralAdmin, 'web')
        ->post(route('central.dashboard.tenants.impersonate', $this->tenant));

    $response->assertStatus(409);

    $impersonationToken = DB::connection(config('tenancy.database.central_connection'))
        ->table('tenant_user_impersonation_tokens')
        ->first();

    expect($impersonationToken->auth_guard)->toBe('tenant')
        ->and($impersonationToken->tenant_id)->toBe($this->tenant->id)
        ->and($impersonationToken->user_id)->toBe($supportUserId)
        ->and($response->headers->get('X-Inertia-Location'))
        ->toBe('http://impersonation.test/token/'.$impersonationToken->token);

    $this->flushHeaders()
        ->actingAs($existingTenantUser, 'tenant')
        ->get($response->headers->get('X-Inertia-Location'))
        ->assertRedirect('http://impersonation.test/dashboard');

    expect(auth('tenant')->id())->toBe($supportUserId)
        ->and(DB::connection(config('tenancy.database.central_connection'))
            ->table('tenant_user_impersonation_tokens')
            ->where('token', $impersonationToken->token)
            ->exists())->toBeFalse();
});

it('forbids a central user without the superadmin role from impersonating a tenant', function (): void {
    $tenant = Tenant::create([
        'id' => 'tenant-impersonation-forbidden',
        'status' => 'active',
    ]);
    $centralUser = CentralUser::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($centralUser, 'web')
        ->post(route('central.dashboard.tenants.impersonate', $tenant))
        ->assertForbidden();
});

it('does not issue a token when the tenant has no central support account', function (): void {
    $this->tenant = Tenant::create([
        'id' => 'tenant-without-support-'.Str::uuid7(),
        'status' => 'active',
    ]);
    DB::purge('tenant');
    File::delete(database_path($this->tenant->database()->getName()));
    File::put(database_path($this->tenant->database()->getName()), '');
    $this->tenant->run(function (): void {
        Schema::connection('tenant')->create('users', function ($table): void {
            $table->string('id')->primary();
            $table->string('email');
            $table->softDeletes();
        });
    });
    $this->tenant->domains()->create(['domain' => 'without-support.test']);

    $centralAdmin = CentralUser::factory()->create([
        'email_verified_at' => now(),
    ]);
    $centralAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $this->from(route('central.dashboard.tenants.index'))
        ->actingAs($centralAdmin, 'web')
        ->post(route('central.dashboard.tenants.impersonate', $this->tenant))
        ->assertRedirect(route('central.dashboard.tenants.index'))
        ->assertSessionHas('error', 'O tenant ainda não tem a conta de suporte central disponível.');

    expect(DB::connection(config('tenancy.database.central_connection'))
        ->table('tenant_user_impersonation_tokens')
        ->where('tenant_id', $this->tenant->id)
        ->exists())->toBeFalse();
});

it('does not issue a token for a tenant that cannot be accessed', function (): void {
    $tenant = Tenant::create([
        'id' => 'suspended-tenant-impersonation',
        'status' => 'suspended',
        'admin_user_id' => 'local-admin-id',
    ]);
    $centralAdmin = CentralUser::factory()->create([
        'email_verified_at' => now(),
    ]);
    $centralAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $this->from(route('central.dashboard.tenants.index'))
        ->actingAs($centralAdmin, 'web')
        ->post(route('central.dashboard.tenants.impersonate', $tenant))
        ->assertRedirect(route('central.dashboard.tenants.index'))
        ->assertSessionHas('error', 'Só é possível aceder a tenants activos ou em período de teste.');

    expect(DB::connection(config('tenancy.database.central_connection'))
        ->table('tenant_user_impersonation_tokens')
        ->where('tenant_id', $tenant->id)
        ->exists())->toBeFalse();
});
