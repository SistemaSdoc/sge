<?php

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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

    expect($tenant->id)->toBe($this->tenantColegio->id);
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
