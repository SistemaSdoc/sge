<?php

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Tenant;
use App\Models\Tenant\QuadroHonra;
use App\Services\Tenant\QuadroHonra\SincronizarQuadrosHonraTrimestrais;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

function prepararTenantParaGeracaoAutomaticaQuadroHonra(string $tenantId, string $anoLectivoId): Tenant
{
    $tenant = Tenant::create(['id' => $tenantId, 'status' => 'active']);
    File::put(database_path($tenant->database()->getName()), '');
    tenancy()->initialize($tenant);

    foreach ([
        'instituicoes' => ['id', 'quadro_honra_media_minima', 'quadro_honra_limite_alunos'],
        'instituicao_curso' => ['id', 'instituicao_id'],
        'curso_tutelado' => ['id', 'instituicao_curso_id'],
        'curso_classe' => ['id', 'curso_tutelado_id'],
        'curso_classe_turno' => ['id', 'curso_classe_id'],
        'turmas' => ['id', 'ano_lectivo_id', 'curso_classe_turno_id'],
        'turma_disciplina_professor' => ['id', 'turma_id'],
        'pauta_status' => ['id', 'turma_disciplina_professor_id', 'periodo', 'status'],
        'quadros_honra' => ['id', 'ano_lectivo_id', 'escopo'],
    ] as $table => $columns) {
        Schema::connection('tenant')->create($table, function ($blueprint) use ($columns): void {
            foreach ($columns as $column) {
                $blueprint->string($column)->nullable();
            }

            $blueprint->softDeletes();
        });
    }

    DB::connection('tenant')->table('instituicoes')->insert([
        'id' => 'instituicao-1',
        'quadro_honra_media_minima' => 14,
        'quadro_honra_limite_alunos' => 10,
    ]);
    DB::connection('tenant')->table('instituicao_curso')->insert([
        'id' => 'instituicao-curso-1',
        'instituicao_id' => 'instituicao-1',
    ]);
    DB::connection('tenant')->table('curso_tutelado')->insert([
        'id' => 'curso-tutelado-1',
        'instituicao_curso_id' => 'instituicao-curso-1',
    ]);
    DB::connection('tenant')->table('curso_classe')->insert([
        'id' => 'curso-classe-1',
        'curso_tutelado_id' => 'curso-tutelado-1',
    ]);
    DB::connection('tenant')->table('curso_classe_turno')->insert([
        'id' => 'curso-classe-turno-1',
        'curso_classe_id' => 'curso-classe-1',
    ]);
    DB::connection('tenant')->table('turmas')->insert([
        'id' => 'turma-1',
        'ano_lectivo_id' => $anoLectivoId,
        'curso_classe_turno_id' => 'curso-classe-turno-1',
    ]);
    DB::connection('tenant')->table('turma_disciplina_professor')->insert([
        'id' => 'disciplina-professor-1',
        'turma_id' => 'turma-1',
    ]);
    DB::connection('tenant')->table('pauta_status')->insert([
        ['id' => 'pauta-1', 'turma_disciplina_professor_id' => 'disciplina-professor-1', 'periodo' => 1, 'status' => 'finalizada'],
        ['id' => 'pauta-2', 'turma_disciplina_professor_id' => 'disciplina-professor-1', 'periodo' => 2, 'status' => 'expirada'],
        ['id' => 'pauta-3', 'turma_disciplina_professor_id' => 'disciplina-professor-1', 'periodo' => 3, 'status' => 'rascunho'],
    ]);
    DB::connection('tenant')->table('quadros_honra')->insert([
        'id' => 'quadro-1',
        'ano_lectivo_id' => $anoLectivoId,
        'escopo' => 'geral:trimestre:1',
    ]);

    tenancy()->end();

    return $tenant;
}

afterEach(function (): void {
    tenancy()->end();

    foreach (['quadro-auto-test'] as $tenantId) {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant) {
            File::delete(database_path($tenant->database()->getName()));
            $tenant->delete();
        }
    }
});

it('processes closed trimesters without deadlines and produces one unified board', function (): void {
    config(['database.connections.central' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge('central');

    Schema::connection('central')->create('tenants', function ($table): void {
        $table->string('id')->primary();
        $table->json('data')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });
    Schema::connection('central')->create('ano_lectivos', function ($table): void {
        $table->string('id')->primary();
        $table->unsignedSmallInteger('ano_inicio')->nullable();
        $table->string('nome');
        $table->dateTime('data_inicio')->nullable();
        $table->dateTime('data_fim')->nullable();
        $table->boolean('activo')->default(false);
        $table->string('estado')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => '2026-09-01',
        'data_fim' => '2027-07-31',
        'activo' => true,
    ]);
    prepararTenantParaGeracaoAutomaticaQuadroHonra('quadro-auto-test', (string) $anoLectivo->id);

    $sincronizar = Mockery::mock(SincronizarQuadrosHonraTrimestrais::class);
    $sincronizar->shouldReceive('sincronizar')
        ->once()
        ->with('instituicao-1', (string) $anoLectivo->id, 1)
        ->andReturn(1);
    $sincronizar->shouldReceive('sincronizar')
        ->once()
        ->with('instituicao-1', (string) $anoLectivo->id, 2)
        ->andReturn(1);
    app()->instance(SincronizarQuadrosHonraTrimestrais::class, $sincronizar);

    Artisan::call('quadros-honra:gerar-automaticos');

    $sincronizar->shouldHaveReceived('sincronizar')->twice();

    $gerarQuadro = Mockery::mock(GerarQuadroHonra::class);
    $gerarQuadro->shouldReceive('handle')
        ->once()
        ->with([
            'ano_lectivo_id' => (string) $anoLectivo->id,
            'periodo' => 2,
            'media_minima' => 14.0,
            'limite_alunos' => 10,
        ], 'instituicao-1', null, true)
        ->andReturn(new QuadroHonra);

    $tenant = Tenant::query()->findOrFail('quadro-auto-test');
    $quantidade = $tenant->run(
        fn (): int => (new SincronizarQuadrosHonraTrimestrais($gerarQuadro))->sincronizar(
            'instituicao-1',
            (string) $anoLectivo->id,
            2,
        ),
    );

    expect($quantidade)->toBe(1);
});
