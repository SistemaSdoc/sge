<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\RegraAvaliacao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\Turno;
use App\Services\Tenant\Core\RegraAcademica\RegraAplicavel\RegraAplicavel;

it('resolves a class rule before a level rule and a general rule', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'INST',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $anoLectivo = AnoLectivo::create([
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => true,
        'estado' => 'em_curso',
    ]);
    $nivel = NivelEnsino::create([
        'nome' => 'Secundário',
        'ordem' => 1,
        'activo' => true,
    ]);
    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);

    $regraGeral = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => null,
        'nivel_ensino_id' => null,
        'nome' => 'Regra Geral',
        'media_minima_aprovacao' => 10,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $regraNivel = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => null,
        'nivel_ensino_id' => $nivel->id,
        'nome' => 'Regra de Nível',
        'media_minima_aprovacao' => 11,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $regraClasse = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivel->id,
        'nome' => 'Regra de Classe',
        'media_minima_aprovacao' => 12,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $regra = RegraAvaliacao::regraAplicavel(
        instituicaoId: $instituicao->id,
        anoLectivoId: $anoLectivo->id,
        classeId: $classe->id,
        nivelEnsinoId: $nivel->id,
    );

    expect($regra)->not->toBeNull();
    expect($regra->id)->toBe($regraClasse->id);
});

it('uses the course offer institution when the tutor institution is null', function () {
    $instituicaoOferta = Instituicao::create([
        'nome' => 'Instituição Oferta',
        'sigla' => 'OFE',
        'tipo' => 'instituto',
        'status' => 1,
    ]);
    $anoLectivo = AnoLectivo::create([
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => true,
        'estado' => 'em_curso',
    ]);
    $nivel = NivelEnsino::create([
        'nome' => 'Secundário',
        'ordem' => 1,
        'activo' => true,
    ]);
    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);
    $turno = Turno::create(['nome' => 'Manhã']);

    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'duracao_anos' => 3,
        'status' => 1,
    ]);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicaoOferta->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => null,
        'tipo_tutela' => 'externa',
        'curso_tutelado_shared_id' => null,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivel->id,
    ]);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $turma = Turma::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'nome' => 'ATI',
        'ano_lectivo_id' => $anoLectivo->id,
    ]);

    $aluno = Aluno::create([
        'instituicao_id' => $instituicaoOferta->id,
        'numero_processo' => '00001',
        'situacao' => 'activo',
    ]);

    $turmaAluno = TurmaAluno::create([
        'turma_id' => $turma->id,
        'aluno_id' => $aluno->id,
        'activo' => true,
        'ano_lectivo_id' => $anoLectivo->id,
        'estado_matricula' => 'activo',
        'resultado_academico' => null,
    ]);

    $regraEsperada = RegraAvaliacao::create([
        'instituicao_id' => $instituicaoOferta->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivel->id,
        'nome' => 'Regra da Oferta da Instituição',
        'media_minima_aprovacao' => 12,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $resolver = app(RegraAplicavel::class);
    $regra = $resolver->resolve($turmaAluno, $classe->id);

    expect($regra)->not->toBeNull();
    expect($regra->id)->toBe($regraEsperada->id);
});
