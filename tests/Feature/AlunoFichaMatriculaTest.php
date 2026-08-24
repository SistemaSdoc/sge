<?php

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Candidato;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turno;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('allows an authorized user to download the aluno ficha matricula pdf', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição do curso',
        'status' => 1,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicao->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $classe = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $classe->id,
        'turno_id' => $turno->id,
    ]);

    $candidato = Candidato::create([
        'nome' => 'João Aluno',
        'bi' => '1234567890',
        'email' => 'joao@example.com',
        'telefone' => '912345678',
        'nacionalidade' => 'Angolana',
        'naturalidade' => 'Luanda',
        'morada' => 'Rua A',
        'filiacao' => 'Pai e Mãe',
        'data_nascimento' => now()->subYears(16),
    ]);

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => AnoLectivo::create([
            'data_inicio' => now()->startOfYear(),
            'data_fim' => now()->endOfYear(),
        ])->id,
        'status' => 'aprovado',
    ]);

    $alunoUser = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);

    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => $alunoUser->id,
        'matricula' => 'MAT-001',
        'situacao' => 'activo',
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);

    $permission = Permission::create(['name' => 'alunos.view']);
    $role = Role::create(['name' => 'Secretaria']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('alunos.ficha-matricula', ['aluno' => $aluno->id]));

    $response->assertStatus(200);
    $response->assertHeaderContains('content-type', 'pdf');
});
