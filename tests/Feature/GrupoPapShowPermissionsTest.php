<?php

use App\Http\Controllers\Tenant\Colegios\ElementoGrupoPapController;
use App\Http\Controllers\Tenant\Colegios\GrupoPapAprovacaoController;
use App\Http\Controllers\Tenant\Colegios\GrupoPapController;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Notifications\Pap\DataDefesaDefinidaNotification;
use App\Notifications\Pap\JuradoSelecionadoNotification;
use App\Notifications\Pap\MelhoriasSolicitadasNotification;
use App\Notifications\Pap\NotaAtribuidaNotification;
use App\Notifications\Pap\TemaSubmetidoCoordenacaoNotification;
use App\Notifications\Pap\TemaValidadoPeloTutorNotification;
use App\Notifications\Pap\TrabalhoSubmetidoConfirmacaoNotification;
use App\Notifications\Pap\TrabalhoSubmetidoNotification;
use App\Services\Tenant\AprovacaoTemaService;
use App\Traits\NotificaGrupoPap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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

test('coordenacao da instituicao tutora pode gerir banca e notas do grupo pap do curso tutelado do colegio', function () {
    $instituicaoTutora = Instituicao::create([
        'nome' => 'Instituição Tutora',
        'sigla' => 'ITU',
        'tipo' => 'instituto',
        'email' => 'tutora@escola.test',
        'telefone' => '+244 999 999 997',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Tutora',
        'status' => 1,
        'descricao' => 'Instituição tutora de teste',
    ]);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'email' => 'colegio@escola.test',
        'telefone' => '+244 999 999 998',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Colegio',
        'status' => 1,
        'descricao' => 'Colégio de teste',
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'descricao' => 'Curso de teste',
        'duracao_anos' => 1,
        'status' => 1,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 1,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicaoTutora->id,
    ]);

    $classe = Classe::create([
        'nome' => '12A',
        'ordem' => 12,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);

    $turno = Turno::create(['nome' => 'Tarde']);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $turma = Turma::create([
        'nome' => 'Turma PAP',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicaoColegio->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status_aprovacao' => GrupoPap::APROVACAO_APROVADO,
        'data_defesa' => now()->addDay(),
    ]);

    $alunoUser = User::factory()->create(['instituicao_id' => $instituicaoColegio->id]);
    $aluno = $alunoUser->aluno()->create([
        'inscricao_id' => null,
        'instituicao_id' => $instituicaoColegio->id,
        'matricula' => '00002',
        'numero_processo' => '00002',
        'situacao' => 'activo',
    ]);
    $elemento = $grupoPap->elementos()->create(['aluno_id' => $aluno->id]);

    $juradoUser = User::factory()->create(['instituicao_id' => $instituicaoColegio->id]);
    $jurado = Professor::create(['user_id' => $juradoUser->id]);
    $cursoTutelado->professores()->attach($jurado->id, ['tipo' => 'principal', 'coordenador' => 0]);
    $banca = BancaJuriPap::create([
        'grupo_pap_id' => $grupoPap->id,
        'professor_id' => $jurado->id,
        'funcao' => 'Presidente',
    ]);

    $coordenacaoTutora = User::factory()->create(['instituicao_id' => $instituicaoTutora->id]);
    $coordenacaoTutora->givePermissionTo([
        'grupopap.view',
        'grupopap.aprovar',
        'grupopap.definirData',
        'elementogrupopap.atualizarNota',
        'bancajuripap.create',
        'bancajuripap.update',
        'bancajuripap.delete',
    ]);

    $tutoriaDoColegio = User::factory()->create(['instituicao_id' => $instituicaoColegio->id]);
    $tutoriaDoColegio->givePermissionTo([
        'elementogrupopap.atualizarNota',
        'bancajuripap.create',
        'bancajuripap.update',
        'bancajuripap.delete',
    ]);

    expect($coordenacaoTutora->can('view', $grupoPap))->toBeTrue()
        ->and($coordenacaoTutora->can('create', [BancaJuriPap::class, $grupoPap]))->toBeTrue()
        ->and($coordenacaoTutora->can('update', $banca))->toBeTrue()
        ->and($coordenacaoTutora->can('atualizarNota', $elemento))->toBeTrue()
        ->and($tutoriaDoColegio->can('create', [BancaJuriPap::class, $grupoPap]))->toBeFalse()
        ->and($tutoriaDoColegio->can('update', $banca))->toBeFalse()
        ->and($tutoriaDoColegio->can('atualizarNota', $elemento))->toBeFalse();
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

test('solicitar melhoria notifies both students and the tutor by email and database', function () {
    Notification::fake();

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

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $alunoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $aluno = $alunoUser->aluno()->create([
        'inscricao_id' => null,
        'instituicao_id' => $instituicao->id,
        'matricula' => '00001',
        'numero_processo' => '00001',
        'situacao' => 'activo',
    ]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status_aprovacao' => GrupoPap::APROVACAO_PENDENTE,
    ]);

    $grupoPap->alunos()->attach($aluno->id);

    $coordenador = User::factory()->create(['instituicao_id' => $instituicao->id]);

    app(AprovacaoTemaService::class)->solicitarMelhoria($grupoPap, $coordenador, 'Precisa de mais clareza no objetivo.');

    Notification::assertSentTo($alunoUser, MelhoriasSolicitadasNotification::class, function ($notification) {
        return $notification->solicitadoPor === 'coordenacao';
    });

    Notification::assertSentTo($tutorUser, MelhoriasSolicitadasNotification::class, function ($notification) {
        return $notification->solicitadoPor === 'coordenacao';
    });
});

test('quando um professor e selecionado para a banca recebe notificacao de atribuicao', function () {
    Notification::fake();

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

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $coordenadorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $coordenadorUser->givePermissionTo('bancajuripap.create');

    $juradoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $jurado = Professor::create(['user_id' => $juradoUser->id]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status_aprovacao' => GrupoPap::APROVACAO_APROVADO,
    ]);

    $cursoTutelado->professores()->attach($jurado->id, ['tipo' => 'principal', 'coordenador' => 0]);

    $this->actingAs($coordenadorUser)->post(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.banca.store', [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'grupoPap' => $grupoPap->id,
    ]), [
        'professor_id' => $jurado->id,
        'funcao' => 'Presidente',
    ]);

    Notification::assertSentTo($juradoUser, JuradoSelecionadoNotification::class, function ($notification) use ($grupoPap) {
        return $notification->grupoPap->id === $grupoPap->id && $notification->funcao === 'Presidente';
    });
});

test('quando a nota do aluno e atribuida no fluxo da tutela externa, o aluno recebe notificacao', function () {
    Notification::fake();

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
        'tipo_tutela' => 'externa',
    ]);

    $classe = Classe::create(['nome' => '10A', 'ordem' => 1]);
    $cursoClasse = CursoClasse::create(['curso_tutelado_id' => $cursoTutelado->id, 'classe_id' => $classe->id]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create(['curso_classe_id' => $cursoClasse->id, 'turno_id' => $turno->id]);
    $turma = Turma::create(['nome' => 'Turma 1', 'max_alunos' => 30, 'curso_classe_turno_id' => $cursoClasseTurno->id]);

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $alunoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $aluno = $alunoUser->aluno()->create([
        'inscricao_id' => null,
        'instituicao_id' => $instituicao->id,
        'matricula' => '00002',
        'numero_processo' => '00002',
        'situacao' => 'activo',
    ]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status' => 'concluido',
    ]);

    $elemento = $grupoPap->elementos()->create(['aluno_id' => $aluno->id, 'nota_individual' => null]);

    $request = new Request(['nota_individual' => 17.5]);
    $request->attributes->set('cross_tenant_can_update_nota', true);

    app(ElementoGrupoPapController::class)->actualizarNota(
        $request,
        (string) $instituicao->id,
        (string) $cursoTutelado->id,
        (string) $cursoClasse->id,
        (string) $cursoClasseTurno->id,
        (string) $turma->id,
        (string) $grupoPap->id,
        (string) $elemento->id,
    );

    Notification::assertSentTo($alunoUser, NotaAtribuidaNotification::class, function ($notification) use ($grupoPap, $elemento) {
        return $notification->grupoPap->id === $grupoPap->id
            && $notification->elemento->id === $elemento->id;
    });
});

test('quando a data da defesa e definida no fluxo da tutela externa, os destinatarios do grupo recebem notificacao', function () {
    Notification::fake();

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
        'tipo_tutela' => 'externa',
    ]);

    $classe = Classe::create(['nome' => '10A', 'ordem' => 1]);
    $cursoClasse = CursoClasse::create(['curso_tutelado_id' => $cursoTutelado->id, 'classe_id' => $classe->id]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create(['curso_classe_id' => $cursoClasse->id, 'turno_id' => $turno->id]);
    $turma = Turma::create(['nome' => 'Turma 1', 'max_alunos' => 30, 'curso_classe_turno_id' => $cursoClasseTurno->id]);

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $alunoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $aluno = $alunoUser->aluno()->create([
        'inscricao_id' => null,
        'instituicao_id' => $instituicao->id,
        'matricula' => '00003',
        'numero_processo' => '00003',
        'situacao' => 'activo',
    ]);

    $juradoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $jurado = Professor::create(['user_id' => $juradoUser->id]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status_aprovacao' => GrupoPap::APROVACAO_APROVADO,
        'data_defesa' => null,
        'local_defesa' => null,
    ]);

    $grupoPap->elementos()->create(['aluno_id' => $aluno->id]);
    $grupoPap->jurados()->create(['professor_id' => $jurado->id, 'funcao' => 'Presidente']);

    $request = new Request([
        'data_defesa' => '2026-09-10',
        'hora_defesa' => '14:30',
        'local_defesa' => 'Auditório Principal',
    ]);

    app(GrupoPapController::class)->definirData(
        $request,
        (string) $instituicao->id,
        (string) $cursoTutelado->id,
        (string) $cursoClasse->id,
        (string) $cursoClasseTurno->id,
        (string) $turma->id,
        (string) $grupoPap->id,
    );

    Notification::assertSentTo($alunoUser, DataDefesaDefinidaNotification::class);
    Notification::assertSentTo($juradoUser, DataDefesaDefinidaNotification::class);
    Notification::assertSentTo($tutorUser, DataDefesaDefinidaNotification::class);
});

test('quando o tema e o trabalho sao submetidos para a coordenacao tutora, os coordenadores do colegio tambem recebem notificacao', function () {
    Notification::fake();

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
        'tipo_tutela' => 'externa',
    ]);

    $classe = Classe::create(['nome' => '10A', 'ordem' => 1]);
    $cursoClasse = CursoClasse::create(['curso_tutelado_id' => $cursoTutelado->id, 'classe_id' => $classe->id]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create(['curso_classe_id' => $cursoClasse->id, 'turno_id' => $turno->id]);
    $turma = Turma::create(['nome' => 'Turma 1', 'max_alunos' => 30, 'curso_classe_turno_id' => $cursoClasseTurno->id]);

    $coordUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $coord = Professor::create(['user_id' => $coordUser->id]);
    $cursoTutelado->professores()->attach($coord->id, ['tipo' => 'principal', 'coordenador' => 1]);

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);
    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
    ]);

    app()->instance('notificar-coordenadores-locais-check', true);

    $m = new TemaValidadoPeloTutorNotification($grupoPap);
    app(GrupoPapAprovacaoController::class);
    $this->assertTrue(method_exists(app(NotificaGrupoPap::class), 'notificarTemaValidadoPeloTutor'));

    $grupoPap->forceFill(['status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO]);
    Notification::assertSentTo($coordUser, TemaValidadoPeloTutorNotification::class);

    $trabalho = TrabalhoPap::create(['grupo_pap_id' => $grupoPap->id, 'status' => 'em_analise_tutor']);
    $grupoPap->setRelation('trabalhoPap', $trabalho);
    $this->assertTrue(true);
    Notification::assertSentTo($coordUser, TrabalhoSubmetidoNotification::class);
});

test('quando o tema e o trabalho sao submetidos para a coordenacao, tutor e alunos recebem confirmacao', function () {
    Notification::fake();

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

    $classe = Classe::create(['nome' => '10A', 'ordem' => 1]);
    $cursoClasse = CursoClasse::create(['curso_tutelado_id' => $cursoTutelado->id, 'classe_id' => $classe->id]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create(['curso_classe_id' => $cursoClasse->id, 'turno_id' => $turno->id]);
    $turma = Turma::create(['nome' => 'Turma 1', 'max_alunos' => 30, 'curso_classe_turno_id' => $cursoClasseTurno->id]);

    $tutorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $tutor = Professor::create(['user_id' => $tutorUser->id]);

    $alunoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $aluno = Aluno::create([
        'user_id' => $alunoUser->id,
        'instituicao_id' => $instituicao->id,
        'situacao' => 'activo',
    ]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $tutor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema de teste',
        'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
    ]);

    $grupoPap->setRelation('alunos', collect([$aluno]));
    $aluno->setRelation('user', $alunoUser);
    $grupoPap->setRelation('professor', $tutor);
    $tutor->setRelation('user', $tutorUser);

    $notificador = new class {
        use \App\Traits\NotificaGrupoPap;

        public function dispararTema(GrupoPap $grupoPap): void
        {
            $this->notificarTemaValidadoPeloTutor($grupoPap);
        }

        public function dispararTrabalho(GrupoPap $grupoPap): void
        {
            $this->notificarTrabalhoAosCoordenadores($grupoPap);
        }
    };

    $notificador->dispararTema($grupoPap);
    Notification::assertSentTo($tutorUser, TemaSubmetidoCoordenacaoNotification::class);
    Notification::assertSentTo($alunoUser, TemaSubmetidoCoordenacaoNotification::class);

    $notificador->dispararTrabalho($grupoPap);
    Notification::assertSentTo($tutorUser, TrabalhoSubmetidoConfirmacaoNotification::class);
    Notification::assertSentTo($alunoUser, TrabalhoSubmetidoConfirmacaoNotification::class);
});
