<?php

use App\Actions\Tenant\User\DeleteUser;
use App\Exceptions\UserRemovalBlockedException;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\InscricaoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses()->group('inscricao');

test('inscricao service uses the selected academic year when creating an inscription', function () {
    $anoLectivoActivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
    ]);

    $anoLectivoAlternativo = AnoLectivo::create([
        'data_inicio' => now()->addYear(),
        'data_fim' => now()->addYears(2),
    ]);

    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Teste',
        'sigla' => 'TESTE',
        'tipo' => 'publica',
        'email' => 'teste@example.com',
        'telefone' => '912345678',
        'provincia' => 'Luanda',
        'endereco' => 'Rua de Teste',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição de teste',
        'status' => 1,
    ]);

    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $service = app(InscricaoService::class);

    $inscricao = $service->criar([
        'nome' => 'Candidato Teste',
        'bi' => '020619207LA055',
        'numero_estudante' => 'ES-001',
        'telefone' => '923456789',
        'email' => 'candidato@example.com',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivoAlternativo->id,
    ]);

    expect($inscricao->ano_lectivo_id)->toBe($anoLectivoAlternativo->id)
        ->and($inscricao->candidato->bi)->toBe('020619207LA055');
});

test('enrollment options exclude courses archived by central', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Matrícula',
        'sigla' => 'MATR',
        'tipo' => 'instituicao',
        'status' => 1,
    ]);

    $cursos = collect([
        Curso::create(['nome' => 'Curso Activo', 'duracao_anos' => 3]),
        Curso::create(['nome' => 'Curso Arquivado', 'duracao_anos' => 3]),
    ]);
    $cursos->last()->delete();

    $classe = Classe::create(['nome' => '10ª Classe', 'ordem' => 10]);
    $turno = Turno::create(['nome' => 'Manhã']);

    foreach ($cursos as $curso) {
        $instituicaoCurso = InstituicaoCurso::create([
            'instituicao_id' => $instituicao->id,
            'curso_id' => $curso->id,
            'duracao_anos' => 3,
        ]);
        $cursoTutelado = CursoTutelado::create([
            'instituicao_curso_id' => $instituicaoCurso->id,
            'instituicao_tutora_id' => $instituicao->id,
        ]);
        $cursoClasse = CursoClasse::create([
            'curso_tutelado_id' => $cursoTutelado->id,
            'classe_id' => $classe->id,
        ]);
        CursoClasseTurno::create([
            'curso_classe_id' => $cursoClasse->id,
            'turno_id' => $turno->id,
        ]);
    }

    $role = Role::findOrCreate('Secretaria', 'tenant');
    $role->givePermissionTo(Permission::findOrCreate('inscricoes.create', 'tenant'));
    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole($role);

    $this->actingAs($user, 'tenant')
        ->get('/dashboard/inscricoes/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/inscricoes/create')
            ->has('cursos', 1)
            ->where('cursos.0.nome', 'Curso Activo'));

    $cursoArquivadoTurnoId = CursoClasseTurno::query()
        ->whereHas('cursoClasse.cursoTutelado.instituicaoCurso', fn ($query) => $query->where('curso_id', $cursos->last()->id))
        ->value('id');

    $this->actingAs($user, 'tenant')
        ->post('/dashboard/inscricoes', [
            'nome' => 'Candidato Curso Arquivado',
            'bi' => '020619207LA999',
            'email' => 'arquivado@example.com',
            'genero' => 'M',
            'nacionalidade' => 'Angolana',
            'naturalidade' => 'Luanda',
            'data_nascimento' => '2005-01-01',
            'municipio' => 'Luanda',
            'curso_classe_turno_id' => $cursoArquivadoTurnoId,
            'turma_id' => '00000000-0000-0000-0000-000000000000',
            'nota_teste' => 12,
        ])
        ->assertSessionHasErrors([
            'curso_classe_turno_id' => 'O curso selecionado está arquivado e não pode receber novas matrículas.',
        ]);

    $this->assertDatabaseMissing('candidatos', ['bi' => '020619207LA999']);
});

test('removing a student deletes empty inscriptions and blocks students with payment history', function () {
    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
    ]);

    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Inscrição',
        'sigla' => 'INSC',
        'tipo' => 'colegio',
    ]);
    $curso = Curso::create(['nome' => 'Curso de Inscrição', 'duracao_anos' => 3]);
    $classe = Classe::create(['nome' => '10ª Classe', 'ordem' => 10]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $utilizadorInscricoes = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $utilizadorInscricoes->givePermissionTo([
        Permission::findOrCreate('inscricoes.viewAny', 'tenant'),
        Permission::findOrCreate('inscricoes.view', 'tenant'),
        Permission::findOrCreate('pagamentos.viewAny', 'tenant'),
        Permission::findOrCreate('pagamentos.view', 'tenant'),
    ]);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::findOrCreate('SuperAdmin', 'tenant'));

    $criarAluno = function (string $nome, string $identificador) use ($instituicao, $cursoClasseTurno, $anoLectivo): array {
        $alunoUser = User::factory()->create([
            'instituicao_id' => $instituicao->id,
            'email' => "aluno{$identificador}@example.com",
        ]);
        $candidato = Candidato::create([
            'user_id' => $alunoUser->id,
            'nome' => $nome,
            'bi' => "020619207LA{$identificador}",
            'numero_estudante' => "ES-{$identificador}",
        ]);
        $inscricao = Inscricao::create([
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'candidato_id' => $candidato->id,
            'ano_lectivo_id' => $anoLectivo->id,
            'status' => 'aprovado',
        ]);
        $aluno = Aluno::create([
            'user_id' => $alunoUser->id,
            'inscricao_id' => $inscricao->id,
            'instituicao_id' => $instituicao->id,
        ]);

        return [$alunoUser, $inscricao, $aluno];
    };

    [$utilizadorSemPagamento, $inscricaoSemPagamento, $alunoSemPagamento] = $criarAluno('Aluno Sem Pagamento', '101');
    [$utilizadorComPagamento, $inscricaoComPagamento, $alunoComPagamento] = $criarAluno('Aluno Com Pagamento', '102');
    $turma = Turma::create([
        'nome' => 'Turma sem histórico',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
    ]);
    DB::table('turma_aluno')->insert([
        'id' => (string) Str::uuid(),
        'turma_id' => $turma->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'aluno_id' => $alunoSemPagamento->id,
        'activo' => true,
        'situacao' => 'activo',
        'is_historico' => false,
        'resultado' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $inscricaoSemPagamento->created_at = now()->subMinutes(2);
    $inscricaoSemPagamento->save();
    $inscricaoComPagamento->created_at = now()->subMinute();
    $inscricaoComPagamento->save();

    $pagamentoComPagamento = Pagamento::create([
        'aluno_id' => $alunoComPagamento->id,
        'instituicao_id' => $instituicao->id,
        'registado_por' => $utilizadorInscricoes->id,
        'data_pagamento' => now()->toDateString(),
        'valor_total' => 1000,
        'metodo' => 'dinheiro',
    ]);

    expect($alunoSemPagamento->podeSerRemovido()['pode_remover'])->toBeTrue()
        ->and($superAdmin->can('delete', $alunoSemPagamento))->toBeTrue()
        ->and($alunoComPagamento->podeSerRemovido()['bloqueios'])->toContain('pagamentos')
        ->and($superAdmin->can('delete', $alunoComPagamento))->toBeFalse();

    app(DeleteUser::class)->handle($utilizadorSemPagamento);

    expect(fn () => app(DeleteUser::class)->handle($utilizadorComPagamento))
        ->toThrow(UserRemovalBlockedException::class, 'pagamentos');

    $this->actingAs($utilizadorInscricoes, 'tenant')
        ->get('/dashboard/inscricoes?ano_lectivo_id='.$anoLectivo->id)
        ->assertInertia(fn ($page) => $page
            ->component('tenant/inscricoes/index')
            ->has('inscricoes.data', 1)
            ->where('inscricoes.data.0.id', $inscricaoComPagamento->id)
            ->where('inscricoes.data.0.candidato', 'Aluno Com Pagamento'));

    $this->actingAs($utilizadorInscricoes, 'tenant')
        ->get('/dashboard/inscricoes/'.$inscricaoComPagamento->id)
        ->assertInertia(fn ($page) => $page
            ->component('tenant/inscricoes/show')
            ->where('inscricao.candidato.nome', 'Aluno Com Pagamento'));

    $this->actingAs($utilizadorInscricoes, 'tenant')
        ->get('/dashboard/pagamentos')
        ->assertInertia(fn ($page) => $page
            ->component('tenant/pagamentos/index')
            ->has('pagamentos.data', 1)
            ->where('pagamentos.data.0.aluno', 'Aluno Com Pagamento'));

    $this->actingAs($utilizadorInscricoes, 'tenant')
        ->get('/dashboard/pagamentos/'.$pagamentoComPagamento->id)
        ->assertInertia(fn ($page) => $page
            ->component('tenant/pagamentos/show')
            ->where('pagamento.aluno', 'Aluno Com Pagamento'));

    $this->assertDatabaseMissing('alunos', ['id' => $alunoSemPagamento->id]);
    $this->assertDatabaseMissing('turma_aluno', ['aluno_id' => $alunoSemPagamento->id]);
    $this->assertSoftDeleted('users', ['id' => $utilizadorSemPagamento->id]);
    $this->assertDatabaseMissing('inscricoes', ['id' => $inscricaoSemPagamento->id]);
    $this->assertDatabaseHas('users', ['id' => $utilizadorComPagamento->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('alunos', ['id' => $alunoComPagamento->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('inscricoes', ['id' => $inscricaoComPagamento->id]);
    $this->assertDatabaseHas('pagamentos', ['aluno_id' => $alunoComPagamento->id]);
});
