<?php

use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Gate;
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

    $coordenador = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $professor = Professor::create([
        'user_id' => $coordenador->id,
        'especialidade' => 'Matemática',
    ]);

    $coordenadorRole = Role::findOrCreate('Coordenador', 'tenant');
    $coordenadorRole->givePermissionTo(Permission::findOrCreate('curso.secretarios.manage', 'tenant'));
    $coordenador->assignRole($coordenadorRole);

    $cursoCoordenado->professores()->attach($professor->id, [
        'tipo' => 'principal',
        'coordenador' => true,
    ]);

    $secretario = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $secretarioRole = Role::findOrCreate('Secretario do Curso', 'tenant');
    $secretarioRole->givePermissionTo(Permission::findOrCreate('curso-tutelado.view', 'tenant'));
    $secretarioRole->givePermissionTo(Permission::findOrCreate('curso-tutelado.viewAny', 'tenant'));
    $secretario->assignRole($secretarioRole);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

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

    expect($cursoCoordenado->secretarios()->whereKey($secretario->id)->exists())->toBeTrue()
        ->and($secretario->fresh()->hasRole('Secretario do Curso'))->toBeTrue()
        ->and(Gate::forUser($secretario->fresh())->allows('view', $cursoCoordenado))->toBeTrue()
        ->and(Gate::forUser($secretario->fresh())->allows('view', $outroCurso))->toBeFalse();

    $this->delete("{$baseUrl}/{$cursoCoordenado->id}/secretarios/{$secretario->id}")
        ->assertSessionHasNoErrors();

    expect($cursoCoordenado->secretarios()->whereKey($secretario->id)->exists())->toBeFalse()
        ->and($secretario->fresh()->hasRole('Secretario do Curso'))->toBeFalse();
});
