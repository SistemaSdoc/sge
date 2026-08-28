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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('grupo pap show exposes granular permissions for elements and banca actions', function () {
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

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status' => 'Em análise',
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->givePermissionTo([
        'elementogrupopap.create',
        'elementogrupopap.atualizarNota',
        'elementogrupopap.delete',
        'bancajuripap.create',
        'bancajuripap.update',
        'bancajuripap.delete',
    ]);

    $response = $this->actingAs($user, 'tenant')->get(route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', [
        'colegio' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'grupoPap' => $grupoPap->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.elementos.create', true)
        ->where('can.elementos.atualizarNota', true)
        ->where('can.elementos.delete', true)
        ->where('can.banca.create', true)
        ->where('can.banca.update', true)
        ->where('can.banca.delete', true)
    );
});
