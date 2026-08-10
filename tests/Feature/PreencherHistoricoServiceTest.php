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
use App\Models\NivelEnsino;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\Turno;
use App\Models\User;
use App\Services\PreencherHistoricoService;

it('mantém a classe em curso quando existe turma_aluno sem notas finalizadas', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'INST',
        'tipo' => 'instituto',
        'email' => 'teste@example.com',
        'telefone' => '123456789',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição',
        'status' => 1,
    ]);

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $classeActual = Classe::create([
        'nome' => '12ª Classe',
        'nivel_ensino' => 'secundario',
        'ordem' => 12,
    ]);

    $classeAnterior = Classe::create([
        'nome' => '11ª Classe',
        'nivel_ensino' => 'secundario',
        'ordem' => 11,
    ]);

    $turno = Turno::create([
        'nome' => 'Manhã',
    ]);

    $nivelEnsino = NivelEnsino::create([
        'nome' => 'Secundário',
        'ordem' => 1,
        'activo' => true,
    ]);

    $cursoClasseActual = CursoClasse::create([
        'classe_id' => $classeActual->id,
        'curso_tutelado_id' => $cursoTutelado->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);

    $cursoClasseAnterior = CursoClasse::create([
        'classe_id' => $classeAnterior->id,
        'curso_tutelado_id' => $cursoTutelado->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);

    $cursoClasseTurnoActual = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseActual->id,
        'turno_id' => $turno->id,
    ]);

    $cursoClasseTurnoAnterior = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasseAnterior->id,
        'turno_id' => $turno->id,
    ]);

    $candidato = Candidato::create([
        'nome' => 'Aluno Teste',
        'bi' => '123456789LA01',
        'numero_estudante' => '1001',
        'morada' => 'Rua de Teste',
        'telefone' => '911111111',
        'email' => 'aluno@example.com',
    ]);

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurnoActual->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);

    $user = User::factory()->create();

    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => $user->id,
        'matricula' => 'MAT-TESTE-001',
        'situacao' => 'activo',
    ]);

    $turmaAnterior = Turma::create([
        'nome' => '11A',
        'curso_classe_turno_id' => $cursoClasseTurnoAnterior->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoLectivo->id,
    ]);

    TurmaAluno::create([
        'aluno_id' => $aluno->id,
        'turma_id' => $turmaAnterior->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'activo' => true,
    ]);

    $pendentes = app(PreencherHistoricoService::class)->obterClassesFaltando($aluno);

    expect($pendentes)->toHaveCount(1)
        ->and($pendentes[0]['em_curso'])->toBeTrue()
        ->and($pendentes[0]['turma_aluno_id'])->not->toBeNull()
        ->and($pendentes[0]['classe'])->toBe('11ª Classe');
});
