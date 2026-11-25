<?php

use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

$tenantDatabasePath = null;

afterEach(function () use (&$tenantDatabasePath): void {
    tenancy()->end();

    if ($tenantDatabasePath !== null) {
        File::delete($tenantDatabasePath);
        $tenantDatabasePath = null;
    }
});

it('syncs the institution type to the central tenant when it changes', function () use (&$tenantDatabasePath): void {
    config(['database.connections.central' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge('central');

    Schema::connection('central')->create('tenants', function ($table): void {
        $table->string('id')->primary();
        $table->string('status')->nullable();
        $table->string('instituicao_nome')->nullable();
        $table->string('instituicao_tipo')->nullable();
        $table->json('data')->nullable();
        $table->timestamps();
    });

    $tenant = Tenant::create([
        'id' => 'tenant-instituicao-tipo',
        'status' => 'active',
        'instituicao_nome' => 'Instituto de Teste',
        'instituicao_tipo' => 'colegio',
    ]);
    $tenantDatabasePath = database_path($tenant->database()->getName());
    File::put($tenantDatabasePath, '');

    $tenant->run(function (): void {
        Schema::connection('tenant')->create('instituicoes', function ($table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('tipo');
            $table->softDeletes();
            $table->timestamps();
        });

        DB::connection('tenant')->table('instituicoes')->insert([
            'id' => 'instituicao-tipo',
            'nome' => 'Instituto de Teste',
            'tipo' => 'colegio',
        ]);

        Instituicao::query()
            ->whereKey('instituicao-tipo')
            ->firstOrFail()
            ->update(['tipo' => 'instituto']);
    });

    expect($tenant->fresh()->instituicao_tipo)->toBe('instituto');
});
