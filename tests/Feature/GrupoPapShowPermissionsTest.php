<?php

use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turma;
use App\Models\Turno;
use App\Models\User;
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

    $response = $this->actingAs($user)->get(route('pap.show', [
        'instituicao' => $instituicao->id,
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

test('grupo pap marks theme correction as allowed for both improvement states', function () {
    expect(GrupoPap::make([
        'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
    ])->podeSerEditado())->toBeTrue()
        ->and(GrupoPap::make([
            'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
        ])->podeSerEditado())->toBeTrue()
        ->and(GrupoPap::make([
            'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
        ])->podeDefinirTema())->toBeTrue();
});
