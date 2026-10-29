<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Notifications\Pap\AlunoRemovidoDoGrupoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

test('grupo pap deletion flashes feedback when blocked by students and when successful', function (): void {
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
    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema',
        'status' => 'Em análise',
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole(Role::findOrCreate('Director', 'tenant'));
    $user->givePermissionTo(['grupopap.delete', 'elementogrupopap.delete']);

    $anoLectivoId = (string) Str::uuid();
    DB::table('ano_lectivos')->insert([
        'id' => $anoLectivoId,
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => false,
        'estado' => 'planeado',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $candidato = Candidato::create([
        'nome' => 'Aluno Teste',
        'bi' => '020619207LA055',
        'numero_estudante' => 'EST-001',
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivoId,
        'status' => 'aprovado',
    ]);
    $alunoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $aluno = Aluno::create([
        'user_id' => $alunoUser->id,
        'inscricao_id' => $inscricao->id,
        'instituicao_id' => $instituicao->id,
        'matricula' => 'MAT-001',
        'situacao' => 'activo',
    ]);
    $elemento = ElementoGrupoPap::create([
        'grupo_pap_id' => $grupoPap->id,
        'aluno_id' => $aluno->id,
    ]);
    Notification::fake();

    $routeParameters = [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'grupoPap' => $grupoPap->id,
    ];

    $blockedResponse = $this->from(route('tenant.dashboard.grupos-pap.index'))
        ->actingAs($user, 'tenant')
        ->delete(route(
            'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.destroy',
            $routeParameters,
        ));

    $blockedResponse->assertRedirect(route('tenant.dashboard.grupos-pap.index'))
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['type'] === 'error'
            && $flash['toast']['message'] === 'Não é possível remover o grupo enquanto tiver alunos. Remova primeiro todos os alunos.');

    $this->assertModelExists($grupoPap);

    $outroGrupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Outro grupo PAP',
        'tema_grupo' => 'Outro tema',
        'status' => 'Em análise',
    ]);
    $mismatchResponse = $this->actingAs($user, 'tenant')->delete(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos.destroy',
        [...$routeParameters, 'grupoPap' => $outroGrupoPap->id, 'elementoGrupoPap' => $elemento->id],
    ));

    $mismatchResponse->assertNotFound();
    $this->assertModelExists($elemento);

    $removeElementoResponse = $this->from(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
        $routeParameters,
    ))->actingAs($user, 'tenant')->delete(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos.destroy',
        [...$routeParameters, 'elementoGrupoPap' => $elemento->id],
    ));

    $removeElementoResponse->assertRedirect(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
        $routeParameters,
    ));
    Notification::assertSentTo($alunoUser, AlunoRemovidoDoGrupoNotification::class, function ($notification, array $channels) use ($alunoUser, $grupoPap): bool {
        $mail = $notification->toMail($alunoUser);

        return in_array('database', $channels, true)
            && in_array('mail', $channels, true)
            && $notification->grupoPap->is($grupoPap)
            && $mail->viewData['nomeAluno'] === 'Aluno Teste';
    });
    $this->assertModelMissing($elemento);

    $aluno->delete();
    $elementoAlunoRemovido = ElementoGrupoPap::create([
        'grupo_pap_id' => $grupoPap->id,
        'aluno_id' => $aluno->id,
    ]);

    $removeElementoAlunoRemovidoResponse = $this->actingAs($user, 'tenant')->delete(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos.destroy',
        [...$routeParameters, 'elementoGrupoPap' => $elementoAlunoRemovido->id],
    ));

    $removeElementoAlunoRemovidoResponse->assertRedirect(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
        $routeParameters,
    ));
    $this->assertModelMissing($elementoAlunoRemovido);

    $response = $this->actingAs($user, 'tenant')->delete(route(
        'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.destroy',
        $routeParameters,
    ));

    $response->assertRedirect(route('tenant.dashboard.grupos-pap.index'))
        ->assertSessionHas('inertia.flash_data', fn (array $flash): bool => $flash['toast']['type'] === 'success'
            && $flash['toast']['message'] === 'Grupo PAP removido com sucesso.');
    $this->assertModelMissing($grupoPap);
});
