<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search scope filters a table by its configured searchable fields', function (): void {
    Classe::create(['nome' => '10ª Classe', 'nivel_ensino' => 'Secundário']);
    Classe::create(['nome' => '1ª Classe', 'nivel_ensino' => 'Primário']);

    $classes = Classe::query()->search('Secundário')->pluck('nome')->all();

    expect($classes)->toBe(['10ª Classe']);
});

test('student search matches assigned turma and class and enrollment shift', function (): void {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso Teste', 'duracao_anos' => 3, 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $classe = Classe::create(['nome' => 'CTI']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $turno = Turno::create(['nome' => 'Noite']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $candidato = Candidato::create([
        'nome' => 'Candidato Teste',
        'bi' => '020619207LA055',
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'instituicao_id' => $instituicao->id,
        'matricula' => 'MAT-2026-0001',
    ]);
    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma C',
    ]);

    $classeAntiga = Classe::create(['nome' => 'ATI']);
    $cursoClasseAntigo = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classeAntiga->id,
    ]);
    $cursoClasseTurnoAntigo = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseAntigo->id,
        'turno_id' => $turno->id,
    ]);
    $turmaAntiga = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurnoAntigo->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma ATI',
    ]);

    $aluno->turmas()->attach($turmaAntiga->id, [
        'activo' => false,
        'situacao' => 'activo',
    ]);
    $aluno->turmas()->attach($turma->id, [
        'activo' => true,
        'situacao' => 'activo',
    ]);

    expect(Aluno::query()->search('Turma C')->pluck('id')->all())->toBe([$aluno->id]);
    expect(Aluno::query()->search('CTI')->pluck('id')->all())->toBe([$aluno->id]);
    expect(Aluno::query()->search('ATI')->pluck('id')->all())->toBe([]);
    expect(Aluno::query()->search('Noite')->pluck('id')->all())->toBe([$aluno->id]);
});
