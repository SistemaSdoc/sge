<?php

use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('professores can be filtered by course function', function (): void {
    $instituicao = Instituicao::create(['nome' => 'Instituição Teste']);
    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 3,
        'status' => 1,
    ]);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
        'nome' => 'Curso Tutelado Teste',
    ]);

    $professoresPorFuncao = [];

    foreach ([
        'tutor_pap' => ['opap' => true, 'grupo_disciplinar' => 'nenhum', 'coordenador' => false],
        'grupo_disciplinar' => ['opap' => false, 'grupo_disciplinar' => 'membro', 'coordenador' => false],
        'coordenador' => ['opap' => false, 'grupo_disciplinar' => 'nenhum', 'coordenador' => true],
    ] as $funcao => $pivot) {
        $professorUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
        $professor = Professor::create(['user_id' => $professorUser->id]);
        $cursoTutelado->professores()->attach($professor->id, [
            'tipo' => 'principal',
            ...$pivot,
        ]);
        $professoresPorFuncao[$funcao] = $professor;
    }

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->givePermissionTo(Permission::findOrCreate('professores.viewAny', 'tenant'));
    $professorSemFuncaoUser = User::factory()->create(['instituicao_id' => $instituicao->id]);
    Professor::create(['user_id' => $professorSemFuncaoUser->id]);

    $this->actingAs($user, 'tenant')
        ->get(route('tenant.dashboard.professores.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('professores.data', 4)
            ->where('filters.funcao', 'todas')
        );

    foreach ($professoresPorFuncao as $funcao => $professor) {
        $this->get(route('tenant.dashboard.professores.index', ['funcao' => $funcao]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('professores.data', 1)
                ->where('professores.data.0.id', $professor->id)
                ->where('filters.funcao', $funcao)
            );
    }
});
