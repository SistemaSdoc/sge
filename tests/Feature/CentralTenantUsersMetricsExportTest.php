<?php

use App\Models\Central\Tenant;
use App\Models\Central\User;
use App\Services\Central\TenantMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create([
        'id' => 'tenant-users-metrics-export-test',
        'status' => 'active',
        'instituicao_nome' => 'Instituição Teste',
    ]);

    File::put(database_path($this->tenant->database()->getName()), '');
    tenancy()->initialize($this->tenant);

    Schema::connection('tenant')->create('users', function ($table): void {
        $table->string('id')->primary();
        $table->string('email');
        $table->timestamp('last_login_at')->nullable();
        $table->softDeletes();
    });
    Schema::connection('tenant')->create('roles', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
    });
    Schema::connection('tenant')->create('model_has_roles', function ($table): void {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->string('model_id');
    });

    tenancy()->end();

    $this->tenant->domains()->create(['domain' => 'metrics.test']);
});

afterEach(function (): void {
    tenancy()->end();

    if (isset($this->tenant)) {
        File::delete(database_path($this->tenant->database()->getName()));
    }
});

test('tenant metrics count users assigned the Subdirector role', function (): void {
    $tenant = Tenant::query()->findOrFail('tenant-users-metrics-export-test');

    $metrics = $tenant->run(function (): array {
        DB::connection('tenant')->table('users')->insert([
            'id' => 'subdirector-user',
            'email' => 'subdirector@metrics.test',
            'last_login_at' => null,
            'deleted_at' => null,
        ]);
        DB::connection('tenant')->table('roles')->insert([
            'id' => 1,
            'name' => 'Subdirector',
            'guard_name' => 'tenant',
        ]);
        DB::connection('tenant')->table('model_has_roles')->insert([
            'role_id' => 1,
            'model_type' => App\Models\Tenant\User::class,
            'model_id' => 'subdirector-user',
        ]);

        return (new TenantMetricsService)->getUserMetrics();
    });

    expect($metrics['users']['subdiretores'])->toBe(1);
});

test('superadmin can download the tenant user metrics report as a PDF', function (): void {
    $superAdmin = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $response = $this->actingAs($superAdmin, 'web')
        ->get(route('central.dashboard.tenants.users.metrics.export', $this->tenant));

    $response->assertOk()
        ->assertDownload('metricas-usuarios-instituicao-teste.pdf')
        ->assertStreamed()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->streamedContent())->toStartWith('%PDF-');
});
