<?php

use App\Actions\Tenant\CursoTutelado\CreateCursoTutelado;
use App\Actions\Tenant\CursoTutelado\UpdateCursoTutelado;
use App\Enums\TutelaStatus;
use App\Http\Controllers\Tenant\ExportarPautaController;
use App\Http\Controllers\Tenant\NotificacaoController;
use App\Jobs\Tenant\Tutela\SincronizarAssociacaoTutela;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\AprovacaoTemaService;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\CursoTuteladoViewService;
use App\Services\Tenant\GrupoPapViewService;
use App\Services\Tenant\Tutela\Data\InstituicaoTutoraData;
use App\Services\Tenant\Tutela\TutelaService;
use App\Services\Tenant\Tutela\TutelaTenantService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class);

function createTenantForIsolationTest(string $id): Tenant
{
    $tenant = Tenant::create(['id' => $id, 'status' => 'active']);
    File::put(database_path($tenant->database()->getName()), '');

    Artisan::call('tenants:migrate', [
        '--tenants' => [$tenant->id],
        '--force' => true,
        '--no-interaction' => true,
    ]);

    return $tenant->refresh();
}

function createPapFixtureForIsolationTest(Tenant $tenant, string $sharedId, string $status = 'pendente'): array
{
    return $tenant->run(function () use ($sharedId, $status): array {
        $instituicao = Instituicao::create(['nome' => 'Colegio B', 'tipo' => 'colegio']);
        $curso = Curso::create(['nome' => 'Curso B', 'duracao_anos' => 3]);
        $instituicaoCurso = InstituicaoCurso::create([
            'curso_id' => $curso->id,
            'instituicao_id' => $instituicao->id,
            'duracao_anos' => 3,
        ]);
        $cursoTutelado = CursoTutelado::create([
            'instituicao_curso_id' => $instituicaoCurso->id,
            'instituicao_tutora_id' => $instituicao->id,
            'tipo_tutela' => 'externa',
            'curso_tutelado_shared_id' => $sharedId,
        ]);
        $classe = Classe::create(['nome' => '10A', 'nivel_ensino' => 'medio']);
        $nivel = NivelEnsino::create(['nome' => 'Médio']);
        $cursoClasse = CursoClasse::create([
            'curso_tutelado_id' => $cursoTutelado->id,
            'classe_id' => $classe->id,
            'nivel_ensino_id' => $nivel->id,
        ]);
        $turno = Turno::create(['nome' => 'Manhã']);
        $cursoClasseTurno = CursoClasseTurno::create([
            'curso_classe_id' => $cursoClasse->id,
            'turno_id' => $turno->id,
        ]);
        $ano = AnoLectivo::create([
            'nome' => '2026/2027',
            'data_inicio' => '2026-09-01',
            'data_fim' => '2027-07-31',
        ]);
        $user = User::create([
            'nome' => 'Professor B',
            'email' => 'professor-b@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicao->id,
        ]);
        $professor = Professor::create(['user_id' => $user->id]);
        $turma = Turma::create([
            'nome' => 'A',
            'max_alunos' => 30,
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'ano_lectivo_id' => $ano->id,
        ]);
        $grupo = GrupoPap::create([
            'turma_id' => $turma->id,
            'professor_tutor_id' => $professor->id,
            'nome_grupo' => 'Grupo B',
            'tema_grupo' => 'Tema B',
            'status_aprovacao' => $status,
        ]);

        return compact('grupo', 'cursoTutelado', 'turma');
    });
}

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);

    $this->tenantTutor = createTenantForIsolationTest('tenant-tutor-test');
    $this->tenantColegio = createTenantForIsolationTest('tenant-colegio-test');
    $this->vinculo = CursoTuteladoShared::create([
        'tenant_tutor_id' => $this->tenantTutor->id,
        'tenant_tutelado_id' => $this->tenantColegio->id,
        'curso_tutelado_tutelado_id' => 'curso-pendente',
        'tenant_tutor_nome' => 'Instituto A',
        'curso_nome' => 'Curso B',
        'duracao_anos' => 3,
        'status' => 'activo',
    ]);
    $this->tutor = $this->tenantTutor->run(fn (): User => User::create([
        'nome' => 'Tutor A',
        'email' => 'tutor-a@example.test',
        'password' => 'password',
    ]));
});

afterEach(function (): void {
    tenancy()->end();
    foreach ([$this->tenantTutor, $this->tenantColegio] as $tenant) {
        File::delete(database_path($tenant->database()->getName()));
    }
});

test('tutor de outro tenant nao consegue validar um vinculo que nao lhe pertence', function (): void {
    tenancy()->initialize($this->tenantColegio);
    $this->actingAs($this->tutor, 'tenant');

    $this->expectException(AuthorizationException::class);
    app(CrossTenantAccessService::class)->validarAcessoDoTutorAoColega(
        $this->tutor,
        $this->tenantColegio->id,
        $this->vinculo->id,
    );
});

test('tutor consegue validar acesso ao colegio com vinculo activo', function (): void {
    tenancy()->initialize($this->tenantTutor);
    $this->actingAs($this->tutor, 'tenant');

    $tenant = app(CrossTenantAccessService::class)->validarAcessoDoTutorAoColega(
        $this->tutor,
        $this->tenantColegio->id,
        $this->vinculo->id,
    );

    expect($tenant->id)->toBe($this->tenantColegio->id)
        ->and($this->vinculo->fresh()->status)->toBe(TutelaStatus::ACTIVO);
});

test('curso externo pendente bloqueia operacoes de gestao e rejeitado permite reconfiguracao', function (): void {
    tenancy()->initialize($this->tenantColegio);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio em Espera',
        'tipo' => 'colegio',
        'status' => 1,
    ]);
    $instituicaoTutora = Instituicao::create([
        'nome' => 'Instituto da Tutela',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso em Espera', 'duracao_anos' => 4]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicaoTutora->id,
        'tipo_tutela' => 'externa',
        'curso_tutelado_shared_id' => $this->vinculo->id,
    ]);

    $user = User::create([
        'nome' => 'Secretária do Colégio',
        'email' => 'secretaria-espera@example.test',
        'password' => 'password',
        'instituicao_id' => $instituicaoColegio->id,
    ]);

    $permissionView = Permission::create(['name' => 'curso-tutelado.view', 'guard_name' => 'tenant']);
    $permissionUpdate = Permission::create(['name' => 'curso-tutelado.update', 'guard_name' => 'tenant']);
    $role = Role::create(['name' => 'Secretaria', 'guard_name' => 'tenant']);
    $role->givePermissionTo([$permissionView, $permissionUpdate]);
    $user->assignRole($role);

    $this->vinculo->update(['status' => TutelaStatus::PENDENTE]);

    expect(Gate::forUser($user)->allows('view', $cursoTutelado))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $cursoTutelado))->toBeFalse();

    $this->vinculo->update(['status' => TutelaStatus::REJEITADO]);

    expect(Gate::forUser($user)->allows('update', $cursoTutelado))->toBeTrue();
});

test('lista de cursos resolve para o tutor activo anterior mesmo quando o shared local aponta para um rejeitado', function (): void {
    tenancy()->initialize($this->tenantColegio);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio com tutor antigo',
        'tipo' => 'colegio',
        'status' => 1,
    ]);
    $instituicaoAntiga = Instituicao::create([
        'nome' => 'Instituto Antigo',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $instituicaoNova = Instituicao::create([
        'nome' => 'Instituto Novo',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso com troca rejeitada', 'duracao_anos' => 4]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 4,
    ]);

    $tenantNovo = createTenantForIsolationTest('tenant-novo-tutor');

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicaoNova->id,
        'tipo_tutela' => 'externa',
    ]);

    $sharedAntigo = CursoTuteladoShared::create([
        'tenant_tutor_id' => $this->tenantTutor->id,
        'tenant_tutelado_id' => $this->tenantColegio->id,
        'curso_tutelado_tutelado_id' => $cursoTutelado->id,
        'tenant_tutor_nome' => 'Instituto Antigo',
        'curso_nome' => 'Curso com troca rejeitada',
        'duracao_anos' => 4,
        'status' => TutelaStatus::ACTIVO,
    ]);

    $sharedNovo = CursoTuteladoShared::create([
        'tenant_tutor_id' => $tenantNovo->id,
        'tenant_tutelado_id' => $this->tenantColegio->id,
        'curso_tutelado_tutelado_id' => $cursoTutelado->id,
        'tenant_tutor_nome' => 'Instituto Novo',
        'curso_nome' => 'Curso com troca rejeitada',
        'duracao_anos' => 4,
        'status' => TutelaStatus::REJEITADO,
    ]);

    $cursoTutelado->forceFill([
        'curso_tutelado_shared_id' => $sharedNovo->id,
    ])->save();

    $user = User::create([
        'nome' => 'Diretor',
        'email' => 'diretor@example.test',
        'password' => 'password',
        'instituicao_id' => $instituicaoColegio->id,
    ]);

    $resultado = app(CursoTuteladoViewService::class)->index($instituicaoColegio, $user);

    expect($resultado->items())->toHaveCount(1)
        ->and($resultado->items()[0]['instituicao_tutora'])->toBe('Instituto Antigo');
});

test('tutora consegue exportar pauta de curso remoto atraves do shared activo', function (): void {
    tenancy()->initialize($this->tenantColegio);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio Tutorado',
        'tipo' => 'colegio',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso Remoto', 'duracao_anos' => 4]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 4,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicaoColegio->id,
        'tipo_tutela' => 'externa',
        'curso_tutelado_shared_id' => $this->vinculo->id,
    ]);
    $this->vinculo->update(['curso_tutelado_tutelado_id' => $cursoTutelado->id]);
    $classe = Classe::create(['nome' => '11A', 'nivel_ensino' => 'medio']);
    $nivel = NivelEnsino::create(['nome' => 'Médio']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivel->id,
    ]);
    $turno = Turno::create(['nome' => 'Tarde']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $ano = AnoLectivo::create([
        'nome' => '2026/2027',
        'data_inicio' => '2026-09-01',
        'data_fim' => '2027-07-31',
    ]);
    $turma = Turma::create([
        'nome' => 'A',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $ano->id,
    ]);

    tenancy()->initialize($this->tenantTutor);

    Permission::create(['name' => 'pautas.view', 'guard_name' => 'tenant']);
    $role = Role::create(['name' => 'Tutor', 'guard_name' => 'tenant']);
    $role->givePermissionTo('pautas.view');
    $this->tutor->assignRole($role);
    $this->actingAs($this->tutor, 'tenant');

    $response = app(ExportarPautaController::class)->exportarExcel(
        $cursoTutelado->id,
        $turma->id,
        new Request(['periodo' => '1']),
        true,
        false,
        $this->tutor,
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('text/csv');
});

test('solicitacao pendente bloqueia criacao de turno, turma e disciplina na gestao da classe', function (): void {
    tenancy()->initialize($this->tenantColegio);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio com Tutela Pendente',
        'tipo' => 'colegio',
        'status' => 1,
    ]);
    $instituicaoTutora = Instituicao::create([
        'nome' => 'Instituto da Tutela',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $curso = Curso::create(['nome' => 'Curso em Gestão', 'duracao_anos' => 4]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicaoTutora->id,
        'tipo_tutela' => 'externa',
        'curso_tutelado_shared_id' => $this->vinculo->id,
    ]);

    $nivel = NivelEnsino::create(['nome' => 'Médio']);
    $classe = Classe::create(['nome' => '10A', 'nivel_ensino' => 'medio']);
    $cursoClasse = CursoClasse::create([
        'classe_id' => $classe->id,
        'curso_tutelado_id' => $cursoTutelado->id,
        'nivel_ensino_id' => $nivel->id,
    ]);

    $user = User::create([
        'nome' => 'Gestor do Colégio',
        'email' => 'gestor-tutela-pendente@example.test',
        'password' => 'password',
        'instituicao_id' => $instituicaoColegio->id,
    ]);

    Permission::create(['name' => 'curso-tutelado.update', 'guard_name' => 'tenant']);
    Permission::create(['name' => 'cursoclasseturno.create', 'guard_name' => 'tenant']);
    Permission::create(['name' => 'classeturnodisciplina.create', 'guard_name' => 'tenant']);
    Permission::create(['name' => 'turmas.create', 'guard_name' => 'tenant']);

    $role = Role::create(['name' => 'Gestor Curso', 'guard_name' => 'tenant']);
    $role->givePermissionTo([
        'curso-tutelado.update',
        'cursoclasseturno.create',
        'classeturnodisciplina.create',
        'turmas.create',
    ]);
    $user->assignRole($role);

    $this->vinculo->update(['status' => TutelaStatus::PENDENTE]);

    expect(Gate::forUser($user)->allows('update', $cursoTutelado))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', CursoClasseTurno::class))->toBeTrue();
});

test('reenvia a solicitacao quando o curso externo foi rejeitado e o tutor permanece o mesmo', function (): void {
    tenancy()->initialize($this->tenantColegio);

    $instituicaoTutora = $this->tenantTutor->run(fn (): Instituicao => Instituicao::create([
        'nome' => 'Instituto Reaberto',
        'tipo' => 'instituto',
        'status' => 1,
    ]));
    $this->tenantTutor->update(['instituicao_id' => $instituicaoTutora->id]);

    $instituicaoColegio = Instituicao::create([
        'nome' => 'Colégio Reaberto',
        'tipo' => 'colegio',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso Reaberto', 'duracao_anos' => 4]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicaoColegio->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => null,
        'tipo_tutela' => 'externa',
        'curso_tutelado_shared_id' => $this->vinculo->id,
    ]);

    $this->vinculo->update(['status' => TutelaStatus::REJEITADO]);

    app(UpdateCursoTutelado::class)->handle(
        $instituicaoColegio,
        $cursoTutelado,
        [
            'tenant_tutor_id' => (string) $this->tenantTutor->getTenantKey(),
            'duracao_anos' => 4,
            'classes' => [],
        ],
    );

    expect($this->vinculo->fresh()->status)->toBe(TutelaStatus::PENDENTE);
});

test('nao permite associar um vinculo a partir do tenant errado', function (): void {
    $fixture = createPapFixtureForIsolationTest($this->tenantColegio, $this->vinculo->id);

    tenancy()->initialize($this->tenantTutor);

    expect(fn (): mixed => app(TutelaTenantService::class)->associarTutelaExterna(
        $fixture['cursoTutelado'],
        $this->vinculo,
    ))->toThrow(LogicException::class, 'não pertence ao tenant actual');
});

test('tutor nao consegue validar acesso depois de encerrar o vinculo', function (): void {
    $this->vinculo->update(['status' => 'encerrado']);
    tenancy()->initialize($this->tenantTutor);
    $this->actingAs($this->tutor, 'tenant');

    $this->expectException(AuthorizationException::class);
    app(CrossTenantAccessService::class)->validarAcessoDoTutorAoColega(
        $this->tutor,
        $this->tenantColegio->id,
        $this->vinculo->id,
    );
});

test('aprovacao externa grava o actor no historico do colegio', function (): void {
    $fixture = createPapFixtureForIsolationTest($this->tenantColegio, $this->vinculo->id);
    $this->vinculo->update(['curso_tutelado_tutelado_id' => $fixture['cursoTutelado']->id]);
    tenancy()->initialize($this->tenantColegio);

    expect(app(AprovacaoTemaService::class)->aprovar(
        $fixture['grupo'],
        $this->tutor,
        'Aprovado pelo instituto.',
        $this->tenantTutor->id,
    ))->toBeTrue();

    $historico = $fixture['grupo']->historicoAprovacao()->first();
    expect($historico->utilizador_id)->toBeNull()
        ->and($historico->utilizador_externo_id)->toBe($this->tutor->id)
        ->and($historico->utilizador_externo_tenant_id)->toBe($this->tenantTutor->id)
        ->and($historico->utilizador_nome)->toBe($this->tutor->nome);
});

test('solicitacao de tutela grava a notificacao no tenant tutor', function (): void {
    $tenantTutorAdmin = $this->tenantTutor->run(function (): User {
        $instituicao = Instituicao::create(['nome' => 'Instituto Tutor', 'tipo' => 'instituto']);

        return User::create([
            'nome' => 'Administrador Tutor',
            'email' => 'sem-email',
            'instituicao_id' => $instituicao->id,
        ]);
    });
    $this->tenantTutor->update(['admin_user_id' => $tenantTutorAdmin->id]);

    $colegioInstituicaoId = $this->tenantColegio->run(function (): string {
        $instituicao = Instituicao::create(['nome' => 'Colégio Tutelado', 'tipo' => 'colegio']);
        $curso = Curso::create(['nome' => 'Contabilidade', 'duracao_anos' => 4]);
        $instituicaoCurso = InstituicaoCurso::create([
            'instituicao_id' => $instituicao->id,
            'curso_id' => $curso->id,
            'duracao_anos' => 4,
        ]);

        $instituicaoCurso->cursoTutelado()->create([
            'tipo_tutela' => 'externa',
        ]);

        return $instituicao->id;
    });
    $this->tenantColegio->update(['instituicao_id' => $colegioInstituicaoId]);

    $cursoTutelado = $this->tenantColegio->run(
        fn (): CursoTutelado => CursoTutelado::latest()->firstOrFail()
    );

    tenancy()->initialize($this->tenantColegio);

    $instituicaoTutora = $this->tenantTutor->run(
        fn (): Instituicao => Instituicao::findOrFail($tenantTutorAdmin->instituicao_id)
    );

    app(TutelaService::class)->publicarEAssociarCurso(
        $cursoTutelado,
        new InstituicaoTutoraData($this->tenantTutor, $instituicaoTutora),
    );

    $notificationCount = $this->tenantTutor->run(
        fn (): int => User::findOrFail($tenantTutorAdmin->id)->notifications()->count()
    );

    expect($notificationCount)->toBe(1);
});

test('troca de tutela notifica o tutor antigo primeiro e depois o novo apos aprovacao', function (): void {
    $instituicaoTutorAntigo = $this->tenantTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Antigo',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutorAntigo = $this->tenantTutor->run(function () use ($instituicaoTutorAntigo): User {
        return User::create([
            'nome' => 'Administrador Antigo',
            'email' => 'admin-antigo@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutorAntigo->id,
        ]);
    });
    $this->tenantTutor->update([
        'instituicao_id' => $instituicaoTutorAntigo->id,
        'admin_user_id' => $adminTutorAntigo->id,
    ]);

    $tenantNovoTutor = createTenantForIsolationTest('tenant-tutor-novo-test');
    $instituicaoTutorNovo = $tenantNovoTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Novo',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutorNovo = $tenantNovoTutor->run(function () use ($instituicaoTutorNovo): User {
        return User::create([
            'nome' => 'Administrador Novo',
            'email' => 'admin-novo@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutorNovo->id,
        ]);
    });
    $tenantNovoTutor->update([
        'instituicao_id' => $instituicaoTutorNovo->id,
        'admin_user_id' => $adminTutorNovo->id,
    ]);

    $instituicaoColegio = $this->tenantColegio->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Colégio Flow',
            'tipo' => 'colegio',
            'status' => 1,
        ]);
    });
    $this->tenantColegio->update(['instituicao_id' => $instituicaoColegio->id]);

    tenancy()->initialize($this->tenantColegio);

    $classe = Classe::create(['nome' => '12A', 'nivel_ensino' => 'medio']);
    $nivel = NivelEnsino::create(['nome' => 'Médio']);

    $cursoTutelado = app(CreateCursoTutelado::class)->handle($instituicaoColegio, [
        'nome' => 'Curso Troca',
        'duracao_anos' => 4,
        'nivel_ensino_id' => $nivel->id,
        'classe_ids' => [$classe->id],
        'tenant_tutor_id' => $this->tenantTutor->id,
    ]);

    $sharedInicial = $cursoTutelado->fresh()->cursoTuteladoShared;
    $sharedInicial->update(['status' => TutelaStatus::ACTIVO]);

    app(UpdateCursoTutelado::class)->handle($instituicaoColegio, $cursoTutelado->fresh(), [
        'nome' => 'Curso Troca',
        'duracao_anos' => 4,
        'classes' => [$classe->id],
        'nivel_ensino_id' => $nivel->id,
        'tenant_tutor_id' => $tenantNovoTutor->id,
    ]);

    $sharedTroca = $cursoTutelado->fresh()->cursoTuteladoShared;

    expect($sharedTroca->status)->toBe(TutelaStatus::PENDENTE)
        ->and($this->tenantTutor->run(fn (): int => User::findOrFail($adminTutorAntigo->id)->notifications()->where('data->tipo', 'troca_tutela')->count()))->toBe(1)
        ->and($tenantNovoTutor->run(fn (): int => User::findOrFail($adminTutorNovo->id)->notifications()->where('data->tipo', 'solicitacao_tutela')->count()))->toBe(0);

    $notificationOld = $this->tenantTutor->run(fn () => User::findOrFail($adminTutorAntigo->id)->notifications()->where('data->tipo', 'troca_tutela')->first());

    $request = new Request;
    $request->setUser($this->tenantTutor->run(fn () => User::findOrFail($adminTutorAntigo->id)));

    app(NotificacaoController::class)->aprovarTutela($request, $notificationOld->id);

    expect($sharedTroca->fresh()->status)->toBe(TutelaStatus::ACTIVO)
        ->and($tenantNovoTutor->run(fn (): int => User::findOrFail($adminTutorNovo->id)->notifications()->where('data->tipo', 'solicitacao_tutela')->count()))->toBe(1);
});

test('edicao de tutela propria para externa envia solicitacao ao instituto escolhido', function (): void {
    $instituicaoTutor = $this->tenantTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Editar',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutor = $this->tenantTutor->run(function () use ($instituicaoTutor): User {
        return User::create([
            'nome' => 'Administrador Tutor Editar',
            'email' => 'admin-editar@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutor->id,
        ]);
    });
    $this->tenantTutor->update([
        'instituicao_id' => $instituicaoTutor->id,
        'admin_user_id' => $adminTutor->id,
    ]);

    $instituicaoColegio = $this->tenantColegio->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Colégio Editar',
            'tipo' => 'colegio',
            'status' => 1,
        ]);
    });
    $this->tenantColegio->update(['instituicao_id' => $instituicaoColegio->id]);

    tenancy()->initialize($this->tenantColegio);

    $classe = Classe::create(['nome' => '11A', 'nivel_ensino' => 'medio']);
    $nivel = NivelEnsino::create(['nome' => 'Médio']);

    $cursoTutelado = app(CreateCursoTutelado::class)->handle($instituicaoColegio, [
        'nome' => 'Curso Editar Propria',
        'duracao_anos' => 4,
        'nivel_ensino_id' => $nivel->id,
        'classe_ids' => [$classe->id],
        'tenant_tutor_id' => null,
    ]);

    expect($cursoTutelado->tipo_tutela)->toBe('propria')
        ->and($cursoTutelado->curso_tutelado_shared_id)->toBeNull();

    app(UpdateCursoTutelado::class)->handle($instituicaoColegio, $cursoTutelado->fresh(), [
        'nome' => 'Curso Editar Propria',
        'duracao_anos' => 4,
        'classes' => [$classe->id],
        'nivel_ensino_id' => $nivel->id,
        'tenant_tutor_id' => $this->tenantTutor->id,
    ]);

    $cursoAtualizado = $cursoTutelado->fresh();
    $shared = $cursoAtualizado->cursoTuteladoShared;

    expect($cursoAtualizado->tipo_tutela)->toBe('externa')
        ->and($shared)->not->toBeNull()
        ->and($shared->status)->toBe(TutelaStatus::PENDENTE)
        ->and($this->tenantTutor->run(fn (): int => User::findOrFail($adminTutor->id)->notifications()->where('data->tipo', 'solicitacao_tutela')->count()))->toBe(1);
});

test('rejeicao de troca de tutela marca pedido como rejeitado e restaura tutor anterior', function (): void {
    $instituicaoTutorAntigo = $this->tenantTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Antigo Rejeicao',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutorAntigo = $this->tenantTutor->run(function () use ($instituicaoTutorAntigo): User {
        return User::create([
            'nome' => 'Administrador Antigo Rejeicao',
            'email' => 'admin-antigo-rejeicao@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutorAntigo->id,
        ]);
    });
    $this->tenantTutor->update([
        'instituicao_id' => $instituicaoTutorAntigo->id,
        'admin_user_id' => $adminTutorAntigo->id,
    ]);

    $tenantNovoTutor = createTenantForIsolationTest('tenant-tutor-novo-rejeicao-test');
    $instituicaoTutorNovo = $tenantNovoTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Novo Rejeicao',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutorNovo = $tenantNovoTutor->run(function () use ($instituicaoTutorNovo): User {
        return User::create([
            'nome' => 'Administrador Novo Rejeicao',
            'email' => 'admin-novo-rejeicao@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutorNovo->id,
        ]);
    });
    $tenantNovoTutor->update([
        'instituicao_id' => $instituicaoTutorNovo->id,
        'admin_user_id' => $adminTutorNovo->id,
    ]);

    $instituicaoColegio = $this->tenantColegio->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Colégio Rejeição',
            'tipo' => 'colegio',
            'status' => 1,
        ]);
    });
    $this->tenantColegio->update(['instituicao_id' => $instituicaoColegio->id]);

    tenancy()->initialize($this->tenantColegio);

    $classe = Classe::create(['nome' => '12B', 'nivel_ensino' => 'medio']);
    $nivel = NivelEnsino::create(['nome' => 'Médio']);

    $cursoTutelado = app(CreateCursoTutelado::class)->handle($instituicaoColegio, [
        'nome' => 'Curso Rejeicao',
        'duracao_anos' => 4,
        'nivel_ensino_id' => $nivel->id,
        'classe_ids' => [$classe->id],
        'tenant_tutor_id' => $this->tenantTutor->id,
    ]);

    $sharedInicial = $cursoTutelado->fresh()->cursoTuteladoShared;
    $sharedInicial->update(['status' => TutelaStatus::ACTIVO]);

    app(UpdateCursoTutelado::class)->handle($instituicaoColegio, $cursoTutelado->fresh(), [
        'nome' => 'Curso Rejeicao',
        'duracao_anos' => 4,
        'classes' => [$classe->id],
        'nivel_ensino_id' => $nivel->id,
        'tenant_tutor_id' => $tenantNovoTutor->id,
    ]);

    $sharedTroca = $cursoTutelado->fresh()->cursoTuteladoShared;
    expect($sharedTroca->status)->toBe(TutelaStatus::PENDENTE)
        ->and($sharedTroca->tenant_tutor_id)->toBe($tenantNovoTutor->id);

    $notificationOld = $this->tenantTutor->run(fn () => User::findOrFail($adminTutorAntigo->id)->notifications()->where('data->tipo', 'troca_tutela')->first());

    $request = new Request;
    $request->setUser($this->tenantTutor->run(fn () => User::findOrFail($adminTutorAntigo->id)));

    app(NotificacaoController::class)->rejeitarTutela($request, $notificationOld->id);

    expect($sharedTroca->fresh()->status)->toBe(TutelaStatus::REJEITADO)
        ->and($sharedTroca->fresh()->tenant_tutor_id)->toBe($this->tenantTutor->id)
        ->and($cursoTutelado->fresh()->tipo_tutela)->toBe('externa');
});

test('fluxo end-to-end de tutela publica, aprova e converte para propria', function (): void {
    $instituicaoTutor = $this->tenantTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor Flow',
            'tipo' => 'instituto',
            'status' => 1,
        ]);
    });
    $adminTutor = $this->tenantTutor->run(function () use ($instituicaoTutor): User {
        return User::create([
            'nome' => 'Administrador Tutor Flow',
            'email' => 'admin-flow@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutor->id,
        ]);
    });
    $this->tenantTutor->update([
        'instituicao_id' => $instituicaoTutor->id,
        'admin_user_id' => $adminTutor->id,
    ]);

    $instituicaoColegio = $this->tenantColegio->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Colégio Flow',
            'tipo' => 'colegio',
            'status' => 1,
        ]);
    });
    $this->tenantColegio->update(['instituicao_id' => $instituicaoColegio->id]);

    tenancy()->initialize($this->tenantColegio);

    $classe = Classe::create(['nome' => '12A', 'nivel_ensino' => 'medio']);
    $nivel = NivelEnsino::create(['nome' => 'Médio']);

    $cursoTutelado = app(CreateCursoTutelado::class)->handle($instituicaoColegio, [
        'nome' => 'Curso Flow',
        'duracao_anos' => 4,
        'nivel_ensino_id' => $nivel->id,
        'classe_ids' => [$classe->id],
        'tenant_tutor_id' => $this->tenantTutor->id,
    ]);

    $shared = CursoTuteladoShared::on(config('tenancy.database.central_connection', config('database.default')))
        ->findOrFail($cursoTutelado->curso_tutelado_shared_id);

    expect($cursoTutelado->tipo_tutela)->toBe('externa')
        ->and($cursoTutelado->curso_tutelado_shared_id)->not->toBeNull()
        ->and($shared->status)->toBe(TutelaStatus::PENDENTE)
        ->and($this->tenantTutor->run(fn (): int => User::findOrFail($adminTutor->id)->notifications()->count()))->toBe(1);

    $shared->update(['status' => TutelaStatus::ACTIVO]);

    app(UpdateCursoTutelado::class)->handle($instituicaoColegio, $cursoTutelado->fresh(), [
        'duracao_anos' => 4,
        'classes' => [$classe->id],
        'tenant_tutor_id' => null,
    ]);

    expect($cursoTutelado->fresh()->tipo_tutela)->toBe('propria')
        ->and($cursoTutelado->fresh()->instituicao_tutora_id)->toBe($instituicaoColegio->id)
        ->and($cursoTutelado->fresh()->curso_tutelado_shared_id)->toBeNull();
});

test('lista cursos tutelados de instituto tutor mescla cursos locais e remotos sem quebrar merge', function (): void {
    tenancy()->initialize($this->tenantTutor);

    $instituicaoTutor = $this->tenantTutor->run(function (): Instituicao {
        return Instituicao::create([
            'nome' => 'Instituto Tutor',
            'tipo' => 'instituto',
            'email' => 'tutor@example.test',
            'telefone' => '+244 912 345 678',
            'provincia' => 'Luanda',
            'endereco' => 'Rua do Instituto',
            'status' => 1,
            'descricao' => 'Instituto tutor',
        ]);
    });

    $user = $this->tenantTutor->run(function () use ($instituicaoTutor): User {
        return User::create([
            'nome' => 'Tutor',
            'email' => 'admin-tutor@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutor->id,
        ]);
    });

    $localCurso = $this->tenantTutor->run(function () use ($instituicaoTutor): CursoTutelado {
        $curso = Curso::create(['nome' => 'Curso Local', 'duracao_anos' => 3]);
        $instituicaoCurso = InstituicaoCurso::create([
            'curso_id' => $curso->id,
            'instituicao_id' => $instituicaoTutor->id,
            'duracao_anos' => 3,
        ]);

        return CursoTutelado::create([
            'instituicao_curso_id' => $instituicaoCurso->id,
            'instituicao_tutora_id' => $instituicaoTutor->id,
        ]);
    });

    $remoteCurso = $this->tenantColegio->run(function (): CursoTutelado {
        $curso = Curso::create(['nome' => 'Curso Remoto', 'duracao_anos' => 4]);
        $instituicao = Instituicao::create([
            'nome' => 'Colégio Remoto',
            'tipo' => 'colegio',
            'email' => 'colegio@example.test',
            'telefone' => '+244 922 345 678',
            'provincia' => 'Luanda',
            'endereco' => 'Rua do Colégio',
            'status' => 1,
            'descricao' => 'Colégio remoto',
        ]);
        $instituicaoCurso = InstituicaoCurso::create([
            'curso_id' => $curso->id,
            'instituicao_id' => $instituicao->id,
            'duracao_anos' => 4,
        ]);

        return CursoTutelado::create([
            'instituicao_curso_id' => $instituicaoCurso->id,
            'instituicao_tutora_id' => $instituicao->id,
            'tipo_tutela' => 'externa',
        ]);
    });

    $shared = CursoTuteladoShared::create([
        'tenant_tutor_id' => $this->tenantTutor->id,
        'tenant_tutelado_id' => $this->tenantColegio->id,
        'curso_tutelado_tutelado_id' => $remoteCurso->getKey(),
        'tenant_tutor_nome' => 'Instituto Tutor',
        'curso_nome' => 'Curso Remoto',
        'duracao_anos' => 4,
        'status' => 'activo',
    ]);

    $user->setRelation('instituicao', $instituicaoTutor);
    $result = app(GrupoPapViewService::class)->tutoredCourses($user);

    expect($result->pluck('nome')->all())
        ->toContain('Curso Local')
        ->toContain('Curso Remoto')
        ->and($result->pluck('id')->all())->toContain((string) $localCurso->getKey())
        ->and($result->pluck('id')->all())->toContain((string) $remoteCurso->getKey());
});

test('publicarEAssociar nao perde o contexto da conexao do tenant', function (): void {
    $tutorData = $this->tenantTutor->run(function (): array {
        $instituicao = Instituicao::create(['nome' => 'Instituto Tutor', 'tipo' => 'instituto']);
        $admin = User::create([
            'nome' => 'Administrador Tutor',
            'email' => 'sem-email',
            'instituicao_id' => $instituicao->id,
        ]);

        return ['instituicao_id' => $instituicao->id, 'admin' => $admin];
    });
    $this->tenantTutor->update([
        'instituicao_id' => $tutorData['instituicao_id'],
        'admin_user_id' => $tutorData['admin']->id,
    ]);

    $colegioData = $this->tenantColegio->run(function (): array {
        $instituicao = Instituicao::create(['nome' => 'Colégio Tutelado', 'tipo' => 'colegio']);
        $classe = Classe::create(['nome' => '10A', 'nivel_ensino' => 'medio']);
        $nivel = NivelEnsino::create(['nome' => 'Médio']);

        return [
            'instituicao' => $instituicao,
            'classe_id' => $classe->id,
            'nivel_ensino_id' => $nivel->id,
        ];
    });
    $this->tenantColegio->update(['instituicao_id' => $colegioData['instituicao']->id]);

    tenancy()->initialize($this->tenantColegio);

    $cursoTutelado = app(CreateCursoTutelado::class)->handle(
        $colegioData['instituicao'],
        [
            'nome' => 'Contabilidade',
            'duracao_anos' => 4,
            'nivel_ensino_id' => $colegioData['nivel_ensino_id'],
            'classe_ids' => [$colegioData['classe_id']],
            'tenant_tutor_id' => $this->tenantTutor->id,
        ],
    );

    $shared = CursoTuteladoShared::findOrFail($cursoTutelado->curso_tutelado_shared_id);
    expect(DB::connection()->getName())->toBe('tenant')
        ->and(tenancy()->tenant->getTenantKey())->toBe($this->tenantColegio->id)
        ->and($cursoTutelado->fresh()->curso_tutelado_shared_id)->toBe($shared->id)
        ->and($shared->tenant_tutelado_id)->toBe($this->tenantColegio->id)
        ->and($shared->tenant_tutor_id)->toBe($this->tenantTutor->id)
        ->and($this->tenantTutor->run(
            fn (): int => User::findOrFail($tutorData['admin']->id)->notifications()->count()
        ))->toBe(1);

    $cursoTutelado->update(['curso_tutelado_shared_id' => null]);

    (new SincronizarAssociacaoTutela(
        tenantTuteladoId: $this->tenantColegio->id,
        cursoTuteladoId: $cursoTutelado->id,
        sharedId: $shared->id,
    ))->handle(app(TutelaTenantService::class));

    expect($cursoTutelado->fresh()->curso_tutelado_shared_id)->toBe($shared->id);
});
