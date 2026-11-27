<?php

use App\Models\Tenant\Turma;
use App\Services\Tenant\Pauta\NotasClasseAnterior;
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

    Schema::create('turmas', function ($table): void {
        $table->string('id')->primary();
        $table->string('curso_classe_turno_id');
    });
    Schema::create('curso_classe_turno', function ($table): void {
        $table->string('id')->primary();
        $table->string('curso_classe_id');
    });
    Schema::create('curso_classe', function ($table): void {
        $table->string('id')->primary();
        $table->string('curso_tutelado_id');
        $table->string('classe_id');
    });
    Schema::create('classes', function ($table): void {
        $table->string('id')->primary();
        $table->string('nome');
        $table->unsignedTinyInteger('ordem');
    });
    Schema::create('turma_aluno', function ($table): void {
        $table->string('id')->primary();
        $table->string('turma_id');
        $table->string('aluno_id');
    });
    Schema::create('notas', function ($table): void {
        $table->string('id')->primary();
        $table->string('turma_aluno_id');
        $table->string('turma_disciplina_professor_id');
        $table->unsignedTinyInteger('periodo');
        $table->decimal('media_trimestral', 5, 2)->nullable();
        $table->decimal('media_final', 5, 2)->nullable();
    });
    Schema::create('turma_disciplina_professor', function ($table): void {
        $table->string('id')->primary();
        $table->string('classe_turno_disciplina_id');
    });
    Schema::create('classe_turno_disciplina', function ($table): void {
        $table->string('id')->primary();
        $table->string('disciplina_id');
    });
});

afterEach(function (): void {
    Schema::dropIfExists('notas');
    Schema::dropIfExists('turma_disciplina_professor');
    Schema::dropIfExists('classe_turno_disciplina');
    Schema::dropIfExists('turma_aluno');
    Schema::dropIfExists('turmas');
    Schema::dropIfExists('curso_classe_turno');
    Schema::dropIfExists('curso_classe');
    Schema::dropIfExists('classes');
});

it('reads grades from all historical classes without requiring confirmation rows', function (): void {
    $studentId = (string) Str::uuid();
    $courseTutoredId = (string) Str::uuid();
    $classIds = [10 => (string) Str::uuid(), 11 => (string) Str::uuid(), 13 => (string) Str::uuid()];
    $turmaIds = [10 => (string) Str::uuid(), 11 => (string) Str::uuid(), 13 => (string) Str::uuid()];
    $enrollmentIds = [10 => (string) Str::uuid(), 11 => (string) Str::uuid(), 13 => (string) Str::uuid()];
    $disciplinaDezId = (string) Str::uuid();
    $disciplinaOnzeId = (string) Str::uuid();

    foreach ([10, 11, 13] as $ordem) {
        $cursoClasseId = (string) Str::uuid();
        $cursoClasseTurnoId = (string) Str::uuid();

        DB::table('classes')->insert([
            'id' => $classIds[$ordem],
            'nome' => "{$ordem}.ª Classe",
            'ordem' => $ordem,
        ]);
        DB::table('curso_classe')->insert([
            'id' => $cursoClasseId,
            'curso_tutelado_id' => $courseTutoredId,
            'classe_id' => $classIds[$ordem],
        ]);
        DB::table('curso_classe_turno')->insert([
            'id' => $cursoClasseTurnoId,
            'curso_classe_id' => $cursoClasseId,
        ]);
        DB::table('turmas')->insert([
            'id' => $turmaIds[$ordem],
            'curso_classe_turno_id' => $cursoClasseTurnoId,
        ]);
        DB::table('turma_aluno')->insert([
            'id' => $enrollmentIds[$ordem],
            'turma_id' => $turmaIds[$ordem],
            'aluno_id' => $studentId,
        ]);
    }

    foreach ([10 => $disciplinaDezId, 11 => $disciplinaOnzeId] as $ordem => $disciplinaId) {
        $classeTurnoDisciplinaId = (string) Str::uuid();
        $professorAssignmentId = (string) Str::uuid();

        DB::table('classe_turno_disciplina')->insert([
            'id' => $classeTurnoDisciplinaId,
            'disciplina_id' => $disciplinaId,
        ]);
        DB::table('turma_disciplina_professor')->insert([
            'id' => $professorAssignmentId,
            'classe_turno_disciplina_id' => $classeTurnoDisciplinaId,
        ]);
        DB::table('notas')->insert(collect([1, 2, 3])
            ->map(fn (int $periodo): array => [
                'id' => (string) Str::uuid(),
                'turma_aluno_id' => $enrollmentIds[$ordem],
                'turma_disciplina_professor_id' => $professorAssignmentId,
                'periodo' => $periodo,
                'media_trimestral' => 12.0,
                'media_final' => $ordem === 10 ? 12.3 : 14.6,
            ])
            ->all());
    }

    $historicalGrades = app(NotasClasseAnterior::class)->obterTodas(
        Turma::query()->findOrFail($turmaIds[13]),
        [$studentId],
    );

    expect($historicalGrades)->toBe([
        'disciplinas' => [
            $disciplinaDezId => ['id' => $disciplinaDezId],
            $disciplinaOnzeId => ['id' => $disciplinaOnzeId],
        ],
        'alunos' => [
            $studentId => [
                10 => [$disciplinaDezId => 12.3],
                11 => [$disciplinaOnzeId => 14.6],
            ],
        ],
    ]);
});
