<?php

use App\Models\Central\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('syncs existing tenant institution names and types to the central database', function (): void {
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
        'id' => 'tenant-sync-instituicao',
        'status' => 'active',
    ]);
    $tenantDatabasePath = database_path($tenant->database()->getName());
    File::put($tenantDatabasePath, '');

    try {
        $tenant->run(function (): void {
            Schema::connection('tenant')->create('instituicoes', function ($table): void {
                $table->string('id')->primary();
                $table->string('nome');
                $table->string('tipo');
                $table->softDeletes();
                $table->timestamps();
            });

            DB::connection('tenant')->table('instituicoes')->insert([
                'id' => 'instituicao-sync',
                'nome' => 'Instituto de Teste',
                'tipo' => 'instituto',
            ]);
        });

        $exitCode = Artisan::call('tenants:sync-nomes');

        expect($exitCode)->toBe(0, Artisan::output())
            ->and($tenant->fresh()->instituicao_nome)->toBe('Instituto de Teste')
            ->and($tenant->fresh()->instituicao_tipo)->toBe('instituto');
    } finally {
        tenancy()->end();
        File::delete($tenantDatabasePath);
    }
});
