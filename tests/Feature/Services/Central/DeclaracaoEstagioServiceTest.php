<?php

use App\Models\Central\DeclaracaoEstagio;
use App\Models\Central\Tenant;
use App\Services\Central\DeclaracaoEstagioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('lists active colleges and institutes with their institution type', function (): void {
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

    Tenant::create([
        'id' => 'colegio-activo',
        'status' => 'active',
        'instituicao_nome' => 'Colégio Central',
        'instituicao_tipo' => 'colegio',
    ]);
    Tenant::create([
        'id' => 'instituto-trial',
        'status' => 'trial',
        'instituicao_nome' => 'Instituto Técnico',
        'instituicao_tipo' => 'instituto',
    ]);
    Tenant::create([
        'id' => 'tenant-pendente',
        'status' => 'pending',
        'instituicao_nome' => 'Colégio Pendente',
        'instituicao_tipo' => 'colegio',
    ]);
    Tenant::create([
        'id' => 'tipo-nao-elegivel',
        'status' => 'active',
        'instituicao_nome' => 'Universidade',
        'instituicao_tipo' => 'universidade',
    ]);

    $instituicoes = app(DeclaracaoEstagioService::class)->listarInstituicoes();

    expect($instituicoes)->toBe([
        [
            'id' => 'colegio-activo',
            'nome' => 'Colégio Central',
            'tipo' => 'colegio',
        ],
        [
            'id' => 'instituto-trial',
            'nome' => 'Instituto Técnico',
            'tipo' => 'instituto',
        ],
    ]);
});

it('registers and lists a declaration emission in the database history', function (): void {
    $declaracao = new DeclaracaoEstagio([
        'tenant_id' => 'colegio-historico',
        'aluno_id' => '10000000-0000-4000-8000-000000000003',
        'aluno_nome' => 'Alexandra António Buco',
        'matricula' => '2026-001',
        'gerado_por_id' => '20000000-0000-4000-8000-000000000003',
        'emitido_em' => now(),
    ]);

    $service = app(DeclaracaoEstagioService::class);
    $historico = $service->registrarHistorico($declaracao);

    expect($historico->exists)->toBeTrue()
        ->and($historico->aluno_nome)->toBe('Alexandra António Buco')
        ->and($service->listarHistorico())->toHaveCount(1)
        ->and($service->listarHistorico()[0]['aluno'])->toBe('Alexandra António Buco');
});
