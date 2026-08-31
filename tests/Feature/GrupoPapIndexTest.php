<?php

use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\GrupoPapViewService;
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

test('grupo pap index returns accessible courses and filters groups by course', function () {
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

    $criarCursoComGrupo = function (string $nome) use ($instituicao): CursoTutelado {
        $curso = Curso::create([
            'nome' => $nome,
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
            'classe_id' => Classe::create(['nome' => '10A', 'ordem' => 1])->id,
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

        GrupoPap::create([
            'turma_id' => $turma->id,
            'nome_grupo' => "Grupo {$nome}",
            'tema_grupo' => 'Tema',
            'status' => 'Em análise',
        ]);

        return $cursoTutelado;
    };

    $cursoA = $criarCursoComGrupo('Curso A');
    $cursoB = $criarCursoComGrupo('Curso B');
    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $service = app(GrupoPapViewService::class);

    $cursos = $service->courses($user, null);
    $todosGrupos = $service->index($user, null);
    $gruposDoCursoA = $service->index($user, null, $cursoA->id);

    expect($cursos->pluck('id')->all())
        ->toEqualCanonicalizing([$cursoA->id, $cursoB->id])
        ->and($todosGrupos->total())->toBe(2)
        ->and($gruposDoCursoA->total())->toBe(1)
        ->and($gruposDoCursoA->first()->turma->cursoClasseTurno->cursoClasse->cursoTutelado->id)
        ->toBe($cursoA->id);
});
test('grupo pap independente exige professor titular do curso do grupo', function () {
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

    $classe = Classe::create(['nome' => '13A', 'ordem' => 13]);
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
        'nome' => 'Turma PAP',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->givePermissionTo('grupopap.create');

    $professor = Professor::create(['user_id' => $user->id, 'especialidade' => 'Matemática']);
    $aluno = 
        \App\Models\Tenant\Aluno::create([
            'user_id' => User::factory()->create(['instituicao_id' => $instituicao->id])->id,
            'inscricao_id' => null,
            'instituicao_id' => $instituicao->id,
            'matricula' => 'MAT-001',
            'situacao' => 'activo',
        ]);

    $response = $this->actingAs($user, 'tenant')->post(route('tenant.dashboard.instituicoes.pap.store', ['instituicao' => $instituicao->id]), [
        'curso_tutelado_id' => $cursoTutelado->id,
        'turma_id' => $turma->id,
        'professor_tutor_id' => $professor->id,
        'nome_grupo' => 'Grupo inválido',
        'alunos' => [$aluno->id],
    ]);

    $response->assertSessionHasErrors('professor_tutor_id');
    $this->assertDatabaseMissing('grupo_pap', [
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo inválido',
    ]);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
