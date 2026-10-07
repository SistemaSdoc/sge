<?php

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Notifications\Pap\TrabalhoSubmetidoNotification;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;
use App\Traits\NotificaGrupoPap;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

function createTenantForDisciplinaryPapVisibility(string $id): Tenant
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

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);

    createTenantForDisciplinaryPapVisibility('pap-discipline-tutor');
    createTenantForDisciplinaryPapVisibility('pap-discipline-colegio');
});

afterEach(function (): void {
    tenancy()->end();

    foreach (['pap-discipline-tutor', 'pap-discipline-colegio'] as $tenantId) {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant) {
            File::delete(database_path($tenant->database()->getName()));
            $tenant->delete();
        }
    }
});

test('membro do grupo disciplinar ve os grupos associados e recebe submissao pap remota sem coordenador', function (): void {
    $tenantTutor = Tenant::query()->findOrFail('pap-discipline-tutor');
    $tenantColegio = Tenant::query()->findOrFail('pap-discipline-colegio');
    $colegio = $tenantColegio->run(
        fn (): Instituicao => Instituicao::create(['nome' => 'Colégio Tutelado PAP', 'tipo' => 'colegio'])
    );
    $tenantColegio->update(['instituicao_id' => $colegio->id]);
    $vinculos = collect(['Curso do grupo disciplinar', 'Outro curso do colégio'])->mapWithKeys(
        fn (string $nome): array => [
            $nome => CursoTuteladoShared::create([
                'tenant_tutor_id' => $tenantTutor->id,
                'tenant_tutelado_id' => $tenantColegio->id,
                'curso_tutelado_tutelado_id' => 'pendente-'.$nome,
                'tenant_tutor_nome' => 'Instituto Tutor PAP',
                'curso_nome' => $nome,
                'duracao_anos' => 3,
                'status' => 'activo',
            ]),
        ]
    );

    $cursosRemotos = $tenantColegio->run(function () use ($colegio, $vinculos): array {
        $classe = Classe::create(['nome' => '13A', 'ordem' => 13]);
        $nivelEnsino = NivelEnsino::create(['nome' => 'Secundário']);
        $turno = Turno::create(['nome' => 'Manhã']);

        return $vinculos->map(function (CursoTuteladoShared $shared, string $nome) use ($colegio, $classe, $nivelEnsino, $turno): array {
            $curso = Curso::create(['nome' => $nome, 'duracao_anos' => 3]);
            $instituicaoCurso = InstituicaoCurso::create([
                'curso_id' => $curso->id,
                'instituicao_id' => $colegio->id,
                'duracao_anos' => 3,
            ]);
            $cursoTutelado = CursoTutelado::create([
                'instituicao_curso_id' => $instituicaoCurso->id,
                'instituicao_tutora_id' => $colegio->id,
                'tipo_tutela' => 'externa',
                'curso_tutelado_shared_id' => $shared->id,
            ]);
            $cursoClasse = CursoClasse::create([
                'curso_tutelado_id' => $cursoTutelado->id,
                'classe_id' => $classe->id,
                'nivel_ensino_id' => $nivelEnsino->id,
            ]);
            $cursoClasseTurno = CursoClasseTurno::create([
                'curso_classe_id' => $cursoClasse->id,
                'turno_id' => $turno->id,
            ]);
            $turma = Turma::create([
                'nome' => 'Turma '.$nome,
                'max_alunos' => 30,
                'curso_classe_turno_id' => $cursoClasseTurno->id,
            ]);
            $grupo = GrupoPap::create([
                'turma_id' => $turma->id,
                'nome_grupo' => 'Grupo PAP '.$nome,
                'tema_grupo' => 'Tema',
                'status' => 'Em análise',
            ]);

            $shared->update([
                'curso_tutelado_tutelado_id' => $cursoTutelado->id,
                'curso_id' => $curso->id,
            ]);

            return ['curso_id' => $curso->id, 'grupo_id' => $grupo->id];
        })->all();
    });

    $membro = $tenantTutor->run(function () use ($cursosRemotos): User {
        $instituicaoTutora = Instituicao::create(['nome' => 'Instituto Tutor PAP', 'tipo' => 'instituto']);
        $instituicaoCurso = InstituicaoCurso::create([
            'curso_id' => $cursosRemotos['Curso do grupo disciplinar']['curso_id'],
            'instituicao_id' => $instituicaoTutora->id,
            'duracao_anos' => 3,
        ]);
        $cursoTutelado = CursoTutelado::create([
            'instituicao_curso_id' => $instituicaoCurso->id,
            'instituicao_tutora_id' => $instituicaoTutora->id,
            'tipo_tutela' => 'propria',
        ]);
        $user = User::create([
            'nome' => 'Membro do Grupo Disciplinar',
            'email' => 'membro-grupo-disciplinar@example.test',
            'password' => 'password',
            'instituicao_id' => $instituicaoTutora->id,
        ]);
        $professor = Professor::create(['user_id' => $user->id]);

        CursoTuteladoProfessor::create([
            'curso_tutelado_id' => $cursoTutelado->id,
            'professor_id' => $professor->id,
            'tipo' => 'colaborador',
            'coordenador' => false,
            'grupo_disciplinar' => 'membro',
        ]);

        $user->assignRole(Role::findOrCreate('Membro do Grupo Disciplinar', 'tenant'));

        return $user;
    });

    tenancy()->initialize($tenantTutor);
    $this->actingAs($membro, 'tenant');

    $grupos = app(GrupoPapViewService::class)->index($membro, null, $colegio->id);

    expect($grupos->getCollection()->pluck('id')->all())
        ->toBe([$cursosRemotos['Curso do grupo disciplinar']['grupo_id']]);

    $accessService = app(CrossTenantAccessService::class);
    $accessService->validarAcessoAoGrupoPap(
        $membro,
        $tenantColegio,
        $cursosRemotos['Curso do grupo disciplinar']['grupo_id'],
        $vinculos['Curso do grupo disciplinar']->id,
    );

    expect(fn () => $accessService->validarAcessoAoGrupoPap(
        $membro,
        $tenantColegio,
        $cursosRemotos['Outro curso do colégio']['grupo_id'],
        $vinculos['Outro curso do colégio']->id,
    ))->toThrow(AuthorizationException::class);

    Notification::fake();

    $notificador = new class
    {
        use NotificaGrupoPap;

        public function enviar(GrupoPap $grupoPap): void
        {
            $this->notificarCoordenadoresDoFluxo(
                $grupoPap,
                new TrabalhoSubmetidoNotification($grupoPap),
            );
        }
    };

    $tenantColegio->run(function () use ($cursosRemotos, $notificador): void {
        $notificador->enviar(GrupoPap::query()->findOrFail($cursosRemotos['Curso do grupo disciplinar']['grupo_id']));
    });

    Notification::assertSentTo($membro, TrabalhoSubmetidoNotification::class);
});
