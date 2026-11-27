<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('allows editing a turma from the course tab and returns to that course', function () {
    $context = createTurmaEditTestContext(['curso-tutelado.view', 'turmas.update']);
    $this->actingAs($context['user'], 'tenant');

    $showResponse = $this->get(route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'ano_lectivo_id' => $context['anoLectivo']->id,
    ]));

    $showResponse->assertOk();
    $showResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->where('cursoTutelado.turmas.data.0.can.edit', true)
    );

    $editResponse = $this->get(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.edit', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'cursoClasse' => $context['cursoClasse'],
        'cursoClasseTurno' => $context['cursoClasseTurno'],
        'turma' => $context['turma'],
        'origem' => 'curso',
        'ano_lectivo_id' => $context['anoLectivo']->id,
    ]));

    $editResponse->assertOk();
    $editResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tenant/cursos-tutelados/classes/turnos/turmas/edit')
        ->where('turma.sala', 'Sala 10')
        ->where('can.update', true)
    );

    $updateResponse = $this->put(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.update', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'cursoClasse' => $context['cursoClasse'],
        'cursoClasseTurno' => $context['cursoClasseTurno'],
        'turma' => $context['turma'],
    ]), [
        'nome' => 'Turma A actualizada',
        'sala' => 'Sala 12',
        'max_alunos' => 25,
        'ano_lectivo_id' => $context['anoLectivo']->id,
        'origem' => 'curso',
    ]);

    $updateResponse->assertRedirect(route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'ano_lectivo_id' => $context['anoLectivo']->id,
    ]));

    $updatedTurma = $context['turma']->refresh();

    expect($updatedTurma->nome)->toBe('Turma A actualizada');
    expect($updatedTurma->sala)->toBe('Sala 12');
    expect((int) $updatedTurma->max_alunos)->toBe(25);
});

it('hides edit permission and forbids users without turma update access', function () {
    $context = createTurmaEditTestContext(['curso-tutelado.view']);
    $this->actingAs($context['user'], 'tenant');

    $showResponse = $this->get(route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'ano_lectivo_id' => $context['anoLectivo']->id,
    ]));

    $showResponse->assertOk();
    $showResponse->assertInertia(fn (AssertableInertia $page) => $page
        ->where('cursoTutelado.turmas.data.0.can.edit', false)
    );

    $this->get(route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.edit', [
        'instituicao' => $context['instituicao'],
        'cursoTutelado' => $context['cursoTutelado'],
        'cursoClasse' => $context['cursoClasse'],
        'cursoClasseTurno' => $context['cursoClasseTurno'],
        'turma' => $context['turma'],
    ]))->assertForbidden();
});

/**
 * @param  array<int, string>  $permissionNames
 * @return array{
 *     instituicao: Instituicao,
 *     cursoTutelado: CursoTutelado,
 *     cursoClasse: CursoClasse,
 *     cursoClasseTurno: CursoClasseTurno,
 *     anoLectivo: AnoLectivo,
 *     turma: Turma,
 *     user: User
 * }
 */
function createTurmaEditTestContext(array $permissionNames): array
{
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $curso = Curso::create(['nome' => 'Curso Teste', 'duracao_anos' => 3, 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $classe = Classe::create(['nome' => '10ª Classe']);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'Turma A',
        'sala' => 'Sala 10',
        'max_alunos' => 20,
    ]);
    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole(Role::create([
        'name' => 'Director',
        'guard_name' => 'tenant',
    ]));
    $user->givePermissionTo(collect($permissionNames)
        ->map(fn (string $name) => Permission::create([
            'name' => $name,
            'guard_name' => 'tenant',
        ]))
        ->all());

    return compact(
        'instituicao',
        'cursoTutelado',
        'cursoClasse',
        'cursoClasseTurno',
        'anoLectivo',
        'turma',
        'user',
    );
}
