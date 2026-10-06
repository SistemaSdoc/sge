<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Classe;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\Menu\SidebarMenuService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('assigns a course secretary only within the coordinator course and removes the course role when unassigned', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 4,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicao->id,
        'duracao_anos' => 4,
    ]);

    $cursoCoordenado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $outroCurso = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subMonth(),
        'data_fim' => now()->addMonths(10),
        'activo' => true,
    ]);

    $cursoClasseAssociado = CursoClasse::create([
        'curso_tutelado_id' => $cursoCoordenado->id,
        'classe_id' => $classe->id,
    ]);
    $turnoAssociado = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseAssociado->id,
        'turno_id' => $turno->id,
    ]);
    $turmaAssociada = Turma::create([
        'curso_classe_turno_id' => $turnoAssociado->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'A',
    ]);

    $cursoClasseNaoAssociado = CursoClasse::create([
        'curso_tutelado_id' => $outroCurso->id,
        'classe_id' => $classe->id,
    ]);
    $turnoNaoAssociado = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseNaoAssociado->id,
        'turno_id' => $turno->id,
    ]);
    $turmaNaoAssociada = Turma::create([
        'curso_classe_turno_id' => $turnoNaoAssociado->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'nome' => 'B',
    ]);

    $coordenador = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professor = Professor::create([
        'user_id' => $coordenador->id,
        'especialidade' => 'Matemática',
    ]);

    $coordenadorRole = Role::findOrCreate('Coordenador', 'tenant');
    $coordenadorRole->givePermissionTo([
        Permission::findOrCreate('curso.secretarios.manage', 'tenant'),
        Permission::findOrCreate('historico.manage', 'tenant'),
        Permission::findOrCreate('alunos.view', 'tenant'),
    ]);
    $coordenador->assignRole($coordenadorRole);

    $cursoCoordenado->professores()->attach($professor->id, [
        'tipo' => 'principal',
        'coordenador' => true,
    ]);

    $secretario = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $secretarioRole = Role::findOrCreate('Secretario do Curso', 'tenant');
    $secretarioRole->givePermissionTo(collect([
        'curso-tutelado.view',
        'curso-tutelado.viewAny',
        'cursoclasse.view',
        'cursoclasseturno.view',
        'turmas.view',
        'grupopap.viewAny',
        'grupopap.view',
        'classeturnodisciplina.view',
        'inscricoes.create',
        'historico.manage',
        'alunos.view',
    ])->map(fn (string $name) => Permission::findOrCreate($name, 'tenant')));
    $professorRole = Role::findOrCreate('Professor', 'tenant');
    $professorRole->givePermissionTo([
        Permission::findOrCreate('notas.create', 'tenant'),
        Permission::findOrCreate('notas.update', 'tenant'),
        Permission::findOrCreate('notas.export', 'tenant'),
    ]);
    $secretario->assignRole($professorRole);
    $secretario->assignRole($secretarioRole);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $secretarioSemRoleProfessor = User::factory()->unverified()->create(['instituicao_id' => $instituicao->id]);
    $secretarioSemRoleProfessor->assignRole($secretarioRole);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($secretarioSemRoleProfessor, 'tenant')
        ->get('/dashboard/settings/appearance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('tenant/settings/appearance'));

    $menuItems = collect(app(SidebarMenuService::class)->build())
        ->pluck('items')
        ->flatten(1)
        ->pluck('key');

    expect($menuItems)->toContain('grupos-pap');

    $baseUrl = "/dashboard/instituicoes/{$instituicao->id}/cursos-tutelados";

    $this->actingAs($coordenador, 'tenant')
        ->post("{$baseUrl}/{$outroCurso->id}/secretarios", ['user_id' => $secretario->id])
        ->assertForbidden();

    $secretariaInstitucional = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $secretariaInstitucional->assignRole(Role::findOrCreate('Secretaria', 'tenant'));

    $this->post("{$baseUrl}/{$cursoCoordenado->id}/secretarios", ['user_id' => $secretariaInstitucional->id])
        ->assertSessionHasErrors('user_id');

    $utilizadorSemRole = User::factory()->create(['instituicao_id' => $instituicao->id]);

    $this->post("{$baseUrl}/{$cursoCoordenado->id}/secretarios", ['user_id' => $utilizadorSemRole->id])
        ->assertSessionHasErrors('user_id');

    $this->post("{$baseUrl}/{$cursoCoordenado->id}/secretarios", ['user_id' => $secretario->id])
        ->assertSessionHasNoErrors();

    $secretaryUser = $secretario->fresh();

    $this->actingAs($secretaryUser, 'tenant')
        ->get("/dashboard/inscricoes/create?curso_tutelado_id={$cursoCoordenado->id}&ano_lectivo_id={$anoLectivo->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/inscricoes/create')
            ->has('cursos', 1)
            ->where('cursos.0.curso_tutelado_id', $cursoCoordenado->id));

    $this->get("/dashboard/pap?search=grupo&curso_id={$curso->id}&ano_lectivo_id={$anoLectivo->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/pap/index')
            ->where('filters.search', 'grupo')
            ->where('filters.curso_id', (string) $curso->id)
            ->where('filters.ano_lectivo_id', (string) $anoLectivo->id)
            ->where('can.selecionarInstituicao', true)
            ->where('can.selecionarAnoLectivo', true)
            ->has('cursosFiltro', 1)
            ->where('cursosFiltro.0.id', (string) $curso->id)
            ->has('gruposPap.data'));

    $registrationData = [
        'nome' => 'Estudante de Teste',
        'bi' => '020619207LA051',
        'email' => 'estudante.curso@example.com',
        'genero' => 'M',
        'nacionalidade' => 'Angolana',
        'naturalidade' => 'Luanda',
        'data_nascimento' => '2007-01-10',
        'municipio' => 'Luanda',
        'curso_classe_turno_id' => $turnoNaoAssociado->id,
        'turma_id' => $turmaNaoAssociada->id,
        'nota_teste' => 8,
        'ano_lectivo_id' => $anoLectivo->id,
    ];

    $this->post('/dashboard/inscricoes', $registrationData)
        ->assertSessionHasErrors('curso_classe_turno_id');

    $this->assertDatabaseMissing('candidatos', ['bi' => '020619207LA051']);

    $registrationData['bi'] = '020619207LA052';
    $registrationData['email'] = 'estudante.associado@example.com';
    $registrationData['curso_classe_turno_id'] = $turnoAssociado->id;
    $registrationData['turma_id'] = $turmaAssociada->id;

    $this->post('/dashboard/inscricoes', $registrationData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoCoordenado->id,
        ]));

    $this->assertDatabaseHas('candidatos', ['bi' => '020619207LA052']);

    expect($cursoCoordenado->secretarios()->whereKey($secretario->id)->exists())->toBeTrue()
        ->and($secretaryUser->hasRole('Secretario do Curso'))->toBeTrue()
        ->and(Gate::forUser($coordenador)->allows('manageSecretarios', $cursoCoordenado))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('manageSecretarios', $cursoCoordenado))->toBeFalse()
        ->and(Gate::forUser($secretario->fresh())->allows('view', $cursoCoordenado))->toBeTrue()
        ->and(Gate::forUser($secretario->fresh())->allows('view', $outroCurso))->toBeFalse();

    $assignedCourseClass = new CursoClasse(['curso_tutelado_id' => $cursoCoordenado->id]);
    $assignedCourseClass->setRelation('cursoTutelado', $cursoCoordenado);
    $unassignedCourseClass = new CursoClasse(['curso_tutelado_id' => $outroCurso->id]);
    $unassignedCourseClass->setRelation('cursoTutelado', $outroCurso);
    $assignedClassTurno = new CursoClasseTurno;
    $assignedClassTurno->setRelation('cursoClasse', $assignedCourseClass);
    $turnoAssociado->setRelation('cursoClasse', $cursoClasseAssociado);
    $turnoNaoAssociado->setRelation('cursoClasse', $cursoClasseNaoAssociado);
    $cursoClasseAssociado->setRelation('cursoTutelado', $cursoCoordenado);
    $cursoClasseNaoAssociado->setRelation('cursoTutelado', $outroCurso);
    $assignedTurma = new Turma;
    $assignedTurma->setRelation('cursoClasseTurno', $assignedClassTurno);
    $assignedDiscipline = new ClasseTurnoDisciplina;
    $assignedDiscipline->setRelation('cursoClasseTurno', $assignedClassTurno);

    expect(Gate::forUser($secretaryUser)->allows('view', $assignedCourseClass))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('view', $unassignedCourseClass))->toBeFalse()
        ->and(Gate::forUser($secretaryUser)->allows('view', $assignedClassTurno))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('view', $assignedTurma))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('view', $assignedDiscipline))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('create', Inscricao::class))->toBeTrue()
        ->and($secretaryUser->can('notas.create'))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('create', Nota::class))->toBeFalse()
        ->and(Gate::forUser($secretaryUser)->allows('export', Nota::class))->toBeFalse();

    $inscricaoDoCurso = new Inscricao;
    $inscricaoDoCurso->setRelation('cursoClasseTurno', $turnoAssociado);
    $alunoDoCurso = new Aluno;
    $alunoDoCurso->id = (string) Str::uuid();
    $alunoDoCurso->setRelation('inscricao', $inscricaoDoCurso);

    $inscricaoDeOutroCurso = new Inscricao;
    $inscricaoDeOutroCurso->setRelation('cursoClasseTurno', $turnoNaoAssociado);
    $alunoDeOutroCurso = new Aluno;
    $alunoDeOutroCurso->id = (string) Str::uuid();
    $alunoDeOutroCurso->setRelation('inscricao', $inscricaoDeOutroCurso);

    expect(Gate::forUser($secretaryUser)->allows('view', $alunoDoCurso))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('manageHistorico', $alunoDoCurso))->toBeTrue()
        ->and(Gate::forUser($secretaryUser)->allows('view', $alunoDeOutroCurso))->toBeFalse()
        ->and(Gate::forUser($secretaryUser)->allows('manageHistorico', $alunoDeOutroCurso))->toBeFalse()
        ->and(Gate::forUser($coordenador)->allows('view', $alunoDoCurso))->toBeTrue()
        ->and(Gate::forUser($coordenador)->allows('manageHistorico', $alunoDoCurso))->toBeTrue();

    $this->actingAs($coordenador, 'tenant');

    $this->delete("{$baseUrl}/{$cursoCoordenado->id}/secretarios/{$secretario->id}")
        ->assertSessionHasNoErrors();

    expect($cursoCoordenado->secretarios()->whereKey($secretario->id)->exists())->toBeFalse()
        ->and($secretario->fresh()->hasRole('Secretario do Curso'))->toBeFalse();
});
