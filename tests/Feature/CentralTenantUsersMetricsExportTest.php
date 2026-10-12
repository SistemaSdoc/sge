<?php

use App\Models\Central\Tenant;
use App\Models\Central\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
