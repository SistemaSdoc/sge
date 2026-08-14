<?php

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Services\DeclaracaoComNotaService;
use App\Services\DeclaracaoSemNotaService;

uses(Tests\TestCase::class);

it('usa o ano lectivo da turma seleccionada na declaração sem notas', function () {
    $anoTurma = new AnoLectivo(['nome' => '2024/25']);
    $anoInscricao = new AnoLectivo(['nome' => '2025/26']);

    $turma = new Turma(['id' => 'turma-1']);
    $turma->setRelation('anoLectivo', $anoTurma);

    $aluno = new Aluno(['id' => 'aluno-1']);
    $inscricao = new Inscricao(['id' => 'inscricao-1']);
    $inscricao->setRelation('anoLectivo', $anoInscricao);
    $aluno->setRelation('inscricao', $inscricao);

    $service = new DeclaracaoSemNotaService();

    expect($service->obterAnoLectivoNome($aluno, $turma))->toBe('2024/25');
});

it('usa o ano lectivo da turma seleccionada na declaração com notas', function () {
    $anoTurma = new AnoLectivo(['nome' => '2023/24']);
    $anoInscricao = new AnoLectivo(['nome' => '2025/26']);

    $turma = new Turma(['id' => 'turma-2']);
    $turma->setRelation('anoLectivo', $anoTurma);

    $cct = new CursoClasseTurno(['id' => 'cct-1']);
    $cursoClasse = new CursoClasse(['id' => 'cc-1']);
    $cursoClasse->setRelation('classe', (object) ['nome' => '10ª']);
    $cct->setRelation('cursoClasse', $cursoClasse);
    $turma->setRelation('cursoClasseTurno', $cct);

    $aluno = new Aluno(['id' => 'aluno-2']);
    $inscricao = new Inscricao(['id' => 'inscricao-2']);
    $inscricao->setRelation('anoLectivo', $anoInscricao);
    $inscricao->setRelation('cursoClasseTurno', $cct);
    $aluno->setRelation('inscricao', $inscricao);

    $service = new DeclaracaoComNotaService();

    expect($service->obterAnoLectivoNome($aluno, $turma))->toBe('2023/24');
});
