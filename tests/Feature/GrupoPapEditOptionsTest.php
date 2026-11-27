<?php

use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

test('grupo pap edit options include its current tutor when not marked for pap', function (): void {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'colegio',
        'email' => 'teste@escola.test',
        'telefone' => '+244 999 999 999',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);
    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'descricao' => 'Curso de teste',
        'duracao_anos' => 1,
        'status' => 1,
    ]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicao->id,
        'duracao_anos' => 1,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => Classe::create(['nome' => '13A', 'ordem' => 13])->id,
    ]);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => Turno::create(['nome' => 'Manhã'])->id,
    ]);
    $turma = Turma::create([
        'nome' => 'Turma PAP',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);
    $professor = Professor::create([
        'user_id' => User::factory()->create(['instituicao_id' => $instituicao->id])->id,
    ]);
    $professor->cursosTutelados()->attach($cursoTutelado->id, ['opap' => false]);
    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $professor->id,
        'nome_grupo' => 'Grupo PAP',
    ]);

    $options = app(GrupoPapViewService::class)->editOptions($cursoTutelado, $turma, $grupoPap);

    expect($options['professores']->pluck('id')->all())->toContain($professor->id);
});
