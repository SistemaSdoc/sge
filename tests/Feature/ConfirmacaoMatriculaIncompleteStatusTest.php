<?php

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\ClasseTurnoDisciplina;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Professor;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Models\Turno;
use App\Models\User;
use App\Services\ConfirmacaoMatriculaService;

it('returns incompleto when there are missing final grades on the confirmation list', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'INST',
        'tipo' => 'publica',
        'email' => 'teste@example.com',
        'telefone' => '911111111',
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

    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
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

    $disciplina = Disciplina::create([
        'nome' => 'Matemática',
        'sigla' => 'MAT',
        'carga_horaria' => 4,
    ]);

    $professorUser = User::factory()->create();
    $professor = Professor::create(['user_id' => $professorUser->id, 'especialidade' => 'Matemática']);

    $classeTurnoDisciplina = ClasseTurnoDisciplina::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'disciplina_id' => $disciplina->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'carga_horaria' => 1,
        'tem_professor' => true,
    ]);

    $turma = Turma::create([
        'nome' => '10A',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'max_alunos' => 40,
        'ano_lectivo_id' => $anoLectivo->id,
    ]);

    TurmaDisciplinaProfessor::create([
        'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
        'turma_id' => $turma->id,
        'professor_id' => $professor->id,
    ]);

    $user = User::factory()->create();

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => null,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);

    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => $user->id,
        'matricula' => 'MAT-001',
        'situacao' => 'activo',
    ]);

    TurmaAluno::create([
        'turma_id' => $turma->id,
        'aluno_id' => $aluno->id,
        'activo' => true,
        'situacao' => 'activo',
    ]);

    $service = app(ConfirmacaoMatriculaService::class);
    $paginator = $service->listarAlunosPorConfirmarMatricula();
    $item = $paginator->items()[0];

    expect($item['status'])->toBe('incompleto');
});
