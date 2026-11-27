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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createGrupoPapTutorDecisionContext(): array
{
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

    $tutor = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professor = Professor::create(['user_id' => $tutor->id]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $professor->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema de teste',
        'problema' => 'Problema de teste',
        'objectivo_geral' => 'Objectivo geral de teste',
        'objectivos_especificos' => 'Objectivos específicos de teste',
        'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
    ]);

    return compact(
        'instituicao',
        'cursoTutelado',
        'cursoClasse',
        'cursoClasseTurno',
        'turma',
        'grupoPap',
        'tutor',
    );
}

dataset('tutor decision forms', [
    'sending theme to coordination' => [
        'tenant.dashboard.grupo-pap-aprovacao.aprovar-tutor.form',
        'tenant.dashboard.grupo-pap-aprovacao.aprovar-tutor',
        'tenant/pap/decisao-tutor/enviar-para-coordenacao',
    ],
    'requesting theme improvement' => [
        'tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria-tutor.form',
        'tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria-tutor',
        'tenant/pap/decisao-tutor/solicitar-melhoria',
    ],
]);

it('renders the dedicated tutor decision page with the group and route context', function (
    string $formRoute,
    string $submitRoute,
    string $component,
) {
    $context = createGrupoPapTutorDecisionContext();
    $params = [
        'instituicao' => $context['instituicao']->id,
        'cursoTutelado' => $context['cursoTutelado']->id,
        'cursoClasse' => $context['cursoClasse']->id,
        'cursoClasseTurno' => $context['cursoClasseTurno']->id,
        'turma' => $context['turma']->id,
        'grupoPap' => $context['grupoPap']->id,
    ];

    $response = $this->actingAs($context['tutor'], 'tenant')
        ->get(route($formRoute, $params));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('grupoPap.nome_grupo', 'Grupo PAP')
            ->where('grupoPap.tema_grupo', 'Tema de teste')
            ->where('turma.nome', 'Turma 1')
            ->where('submitUrl', route($submitRoute, $params))
            ->where('backUrl', route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params))
        );
})->with('tutor decision forms');

it('denies users who are not the tutor access to the tutor decision forms', function (
    string $formRoute,
) {
    $context = createGrupoPapTutorDecisionContext();
    $user = User::factory()->create(['instituicao_id' => $context['instituicao']->id]);
    $params = [
        'instituicao' => $context['instituicao']->id,
        'cursoTutelado' => $context['cursoTutelado']->id,
        'cursoClasse' => $context['cursoClasse']->id,
        'cursoClasseTurno' => $context['cursoClasseTurno']->id,
        'turma' => $context['turma']->id,
        'grupoPap' => $context['grupoPap']->id,
    ];

    $this->actingAs($user, 'tenant')
        ->get(route($formRoute, $params))
        ->assertForbidden();
})->with([
    'coordination form' => ['tenant.dashboard.grupo-pap-aprovacao.aprovar-tutor.form'],
    'improvement form' => ['tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria-tutor.form'],
]);

it('renders the coordination improvement page for an authorized coordinator', function () {
    $context = createGrupoPapTutorDecisionContext();
    $context['tutor']->givePermissionTo('grupopap.solicitarMelhoria');
    $params = [
        'instituicao' => $context['instituicao']->id,
        'cursoTutelado' => $context['cursoTutelado']->id,
        'cursoClasse' => $context['cursoClasse']->id,
        'cursoClasseTurno' => $context['cursoClasseTurno']->id,
        'turma' => $context['turma']->id,
        'grupoPap' => $context['grupoPap']->id,
    ];

    $response = $this->actingAs($context['tutor'], 'tenant')
        ->get(route(
            'tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria-coordenacao.form',
            $params,
        ));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/pap/decisao-coordenacao/solicitar-melhoria')
            ->where('grupoPap.tema_grupo', 'Tema de teste')
            ->where('grupoPap.problema', 'Problema de teste')
            ->where('submitUrl', route(
                'tenant.dashboard.grupo-pap-aprovacao.solicitar-melhoria',
                ['grupoPap' => $context['grupoPap']->id],
            ))
            ->where('backUrl', route(
                'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
                $params,
            ))
        );
});

dataset('coordination decision forms', [
    'approve theme' => [
        'tenant.dashboard.grupo-pap-aprovacao.aprovar-coordenacao.form',
        'tenant/pap/decisao-coordenacao/aprovar',
        'grupopap.aprovar',
        'tenant.dashboard.grupo-pap-aprovacao.aprovar',
    ],
    'reject theme' => [
        'tenant.dashboard.grupo-pap-aprovacao.reprovar-coordenacao.form',
        'tenant/pap/decisao-coordenacao/reprovar',
        'grupopap.reprovar',
        'tenant.dashboard.grupo-pap-aprovacao.reprovar',
    ],
]);

it('renders dedicated coordination decision pages with the correct proposal and submit route', function (
    string $formRoute,
    string $component,
    string $permission,
    string $submitRoute,
) {
    $context = createGrupoPapTutorDecisionContext();
    $context['tutor']->givePermissionTo($permission);
    $params = [
        'instituicao' => $context['instituicao']->id,
        'cursoTutelado' => $context['cursoTutelado']->id,
        'cursoClasse' => $context['cursoClasse']->id,
        'cursoClasseTurno' => $context['cursoClasseTurno']->id,
        'turma' => $context['turma']->id,
        'grupoPap' => $context['grupoPap']->id,
    ];

    $response = $this->actingAs($context['tutor'], 'tenant')
        ->get(route($formRoute, $params));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('grupoPap.tema_grupo', 'Tema de teste')
            ->where('grupoPap.problema', 'Problema de teste')
            ->where('grupoPap.objectivo_geral', 'Objectivo geral de teste')
            ->where('grupoPap.objectivos_especificos', 'Objectivos específicos de teste')
            ->where('submitUrl', route($submitRoute, [
                'grupoPap' => $context['grupoPap']->id,
            ]))
            ->where('backUrl', route(
                'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
                $params,
            ))
        );
})->with('coordination decision forms');
