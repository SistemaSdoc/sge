<?php

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Candidato;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turno;
use App\Models\User;
use App\Services\InscricaoService;
use Spatie\Permission\Models\Role;

uses()->group('inscricao');

test('a professor user can gain aluno role when an inscricao is approved for the same BI', function () {
    $roleProfessor = Role::create(['name' => 'Professor']);
    Role::create(['name' => 'Aluno']);

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

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
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

    $professorUser = User::factory()->create([
        'bi' => '020619207LA055',
        'instituicao_id' => $instituicao->id,
    ]);
    $professorUser->assignRole($roleProfessor);

    Candidato::create([
        'nome' => 'Candidato Teste',
        'bi' => '020619207LA055',
        'numero_estudante' => 'ES-001',
        'telefone' => '923456789',
        'email' => 'candidato@example.com',
    ]);

    $candidato = Candidato::firstWhere('bi', '020619207LA055');

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'pendente',
    ]);

    $service = app(InscricaoService::class);
    $service->atualizarNotaTeste($inscricao, 14);

    $professorUser->refresh();

    expect($professorUser->hasRole('Professor'))->toBeTrue()
        ->and($professorUser->hasRole('Aluno'))->toBeTrue();
});
