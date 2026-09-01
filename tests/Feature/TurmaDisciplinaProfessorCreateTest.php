<?php

use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Inertia\Testing\AssertableInertia as Assert;

test('nested turma disciplina professor create route is available', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Escola Teste',
        'sigla' => 'ET',
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

    $classe = Classe::create([
        'nome' => '10A',
        'ordem' => 1,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $turma = Turma::create([
        'nome' => 'Turma 1',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);

    $disciplina = Disciplina::create([
        'nome' => 'Matemática',
        'sigla' => 'MAT',
        'componente' => 'cientifica',
    ]);

    $classeTurnoDisciplina = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplina->id,
        'carga_horaria' => '90',
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    $response = $this->actingAs($user)->get(
        "/instituicoes/{$instituicao->id}/cursos-tutelados/{$cursoTutelado->id}/classes/{$cursoClasse->id}/turnos/{$cursoClasseTurno->id}/turmas/{$turma->id}/disciplinas/{$classeTurnoDisciplina->id}/professores/create",
    );

    $response->assertOk();
    $response
        ->assertInertia(fn (Assert $page) => $page
            ->component('cursos-tutelados/classes/turnos/turmas/disciplinas/professores/create')
            ->has('professores')
            ->has('disciplinas')
        );
});
