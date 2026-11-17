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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
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

test('um professor tutor consegue corrigir e reenviar o tema sem alterar o nome do grupo pap', function (): void {
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

    $classe = Classe::create(['nome' => '13A', 'ordem' => 13]);
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
        'nome' => 'Turma PAP',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);

    $role = Role::findOrCreate('Professor', 'tenant');
    $user->assignRole($role);
    $user->syncPermissions(['grupopap.update']);

    $professor = Professor::create([
        'user_id' => $user->id,
        'instituicao_id' => $instituicao->id,
        'numero_funcionario' => 'PROF-001',
    ]);

    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'professor_tutor_id' => $professor->id,
        'nome_grupo' => 'Grupo Original',
        'tema_grupo' => 'Tema original',
        'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
    ]);

    Notification::fake();
    $this->actingAs($user, 'tenant');

    $response = $this->put(route('tenant.dashboard.grupo-pap-aprovacao.atualizar', $grupoPap), [
        'tema_grupo' => 'Tema atualizado',
        'problema' => 'Problema atualizado',
        'objectivo_geral' => 'Objectivo geral atualizado',
        'objectivos_especificos' => 'Objectivos específicos atualizados',
    ]);

    $response->assertRedirect();

    $response = $this->post(
        route('tenant.dashboard.grupo-pap-aprovacao.reenviar', $grupoPap),
        [
            'tema_grupo' => 'Tema reenviado',
            'problema' => 'Problema reenviado',
            'objectivo_geral' => 'Objectivo geral reenviado',
            'objectivos_especificos' => 'Objectivos específicos reenviados',
        ],
    );

    $response->assertRedirect();

    $grupoPap->refresh();

    expect($grupoPap->nome_grupo)->toBe('Grupo Original')
        ->and($grupoPap->tema_grupo)->toBe('Tema reenviado')
        ->and($grupoPap->problema)->toBe('Problema reenviado')
        ->and($grupoPap->objectivo_geral)->toBe('Objectivo geral reenviado')
        ->and($grupoPap->objectivos_especificos)->toBe('Objectivos específicos reenviados')
        ->and($grupoPap->status_aprovacao)->toBe(GrupoPap::APROVACAO_SUBMETIDO);
});
