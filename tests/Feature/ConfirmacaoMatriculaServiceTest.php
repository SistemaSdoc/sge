<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\ConfirmacaoMatriculaService;

it('lista alunos que transitam e que repetem para confirmar matrícula no ano seguinte', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Teste',
        'sigla' => 'INST',
        'tipo' => 'publica',
        'email' => 'teste@example.com',
        'telefone' => '123456789',
        'provincia' => 'Luanda',
        'endereco' => 'Rua de Teste',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição',
        'status' => 1,
    ]);

    $classe = Classe::create([
        'nome' => '12ª Classe',
        'ordem' => 12,
    ]);

    $turno = Turno::create([
        'nome' => 'Manhã',
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

    $cursoClasse = CursoClasse::create([
        'classe_id' => $classe->id,
        'curso_tutelado_id' => $cursoTutelado->id,
    ]);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $turma = Turma::create([
        'nome' => '12A',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoLectivo->id,
    ]);

    $candidatoTransita = Candidato::create([
        'nome' => 'Aluno a transitar',
        'bi' => '000000000LA01',
        'numero_estudante' => '0001',
        'morada' => 'Rua A',
        'telefone' => '911111111',
        'email' => 'transita@example.com',
    ]);

    $candidatoRepete = Candidato::create([
        'nome' => 'Aluno a repetir',
        'bi' => '000000000LA02',
        'numero_estudante' => '0002',
        'morada' => 'Rua B',
        'telefone' => '922222222',
        'email' => 'repete@example.com',
    ]);

    $inscricaoTransita = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidatoTransita->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);

    $inscricaoRepete = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidatoRepete->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);

    $userTransita = User::factory()->create();
    $userRepete = User::factory()->create();

    $alunoTransita = Aluno::create([
        'inscricao_id' => $inscricaoTransita->id,
        'user_id' => $userTransita->id,
        'matricula' => 'MAT-TRANSITA',
        'situacao' => 'activo',
    ]);

    $alunoRepete = Aluno::create([
        'inscricao_id' => $inscricaoRepete->id,
        'user_id' => $userRepete->id,
        'matricula' => 'MAT-REPETE',
        'situacao' => 'activo',
    ]);

    TurmaAluno::create([
        'turma_id' => $turma->id,
        'aluno_id' => $alunoTransita->id,
        'activo' => true,
        'situacao' => 'activo',
        'resultado' => 'transita',
    ]);

    TurmaAluno::create([
        'turma_id' => $turma->id,
        'aluno_id' => $alunoRepete->id,
        'activo' => true,
        'situacao' => 'retido',
    ]);

    $service = app(ConfirmacaoMatriculaService::class);
    $result = $service->listarAlunosPorConfirmarMatricula();

    expect($result->items())->toHaveCount(2)
        ->and($result->items())->toContainEqual(expect(fn ($aluno) => $aluno['nome'] === 'Aluno a transitar'))
        ->and($result->items())->toContainEqual(expect(fn ($aluno) => $aluno['nome'] === 'Aluno a repetir'));
});
