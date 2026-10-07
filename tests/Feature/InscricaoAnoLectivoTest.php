<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\InscricaoService;
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
