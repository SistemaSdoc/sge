<?php

use App\Models\Tenant\Aluno;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');

    Schema::create('inscricoes', function ($table): void {
        $table->string('id')->primary();
        $table->string('status');
    });

    Schema::create('alunos', function ($table): void {
        $table->string('id')->primary();
        $table->string('inscricao_id');
        $table->timestamp('deleted_at')->nullable();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('alunos');
    Schema::dropIfExists('inscricoes');
});

it('excludes students with cancelled enrollments from active enrollment queries', function (): void {
    $inscricaoActivaId = (string) Str::uuid();
    $inscricaoCanceladaId = (string) Str::uuid();
    $alunoActivoId = (string) Str::uuid();
    $alunoCanceladoId = (string) Str::uuid();

    DB::table('inscricoes')->insert([
        ['id' => $inscricaoActivaId, 'status' => 'aprovado'],
        ['id' => $inscricaoCanceladaId, 'status' => 'cancelado'],
    ]);
    DB::table('alunos')->insert([
        ['id' => $alunoActivoId, 'inscricao_id' => $inscricaoActivaId],
        ['id' => $alunoCanceladoId, 'inscricao_id' => $inscricaoCanceladaId],
    ]);

    $alunos = Aluno::inscricaoNaoCancelada()->pluck('id')->all();

    expect($alunos)->toBe([$alunoActivoId]);
});
