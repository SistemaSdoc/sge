<?php

namespace App\Services;

use App\Models\Nota;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;

class NotaService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {
    }

    // ──────────────────────────────────────────────
    // LANÇAMENTO DE NOTAS
    // ──────────────────────────────────────────────

    public function lancarNotas(
        array $notas,
        string $tdpId,
        int $periodo
    ): void {

        foreach ($notas as $turmaAlunoId => $dados) {

            $this->gravarNotaPeriodo(
                $turmaAlunoId,
                $tdpId,
                $periodo,
                $dados
            );

            $this->recalcularFinal(
                $turmaAlunoId,
                $tdpId
            );
        }
    }

    // ──────────────────────────────────────────────
    // GRAVAÇÃO POR PERÍODO
    // ──────────────────────────────────────────────

    private function gravarNotaPeriodo(
        string $turmaAlunoId,
        string $tdpId,
        int $periodo,
        array $dados
    ): void {

        // ──────────────────────────────────────────────
        // PERÍODO 4 — recurso
        // Apenas uma nota directa, sem mac/npp/npt
        // ──────────────────────────────────────────────
        if ($periodo === 4) {

            $notaRecurso = isset($dados['nota_recurso'])
                ? (float) $dados['nota_recurso']
                : null;

            $nota = Nota::firstOrNew([
                'turma_aluno_id' => $turmaAlunoId,
                'turma_disciplina_professor_id' => $tdpId,
                'periodo' => 4,
            ]);

            $nota->mac = null;
            $nota->nota_prova_professor = null;
            $nota->nota_prova_trimestral = null;
            $nota->faltas = 0;
            $nota->media_trimestral = $notaRecurso; // ← única nota
            $nota->situacao_trimestral = null;
            $nota->situacao_anual = null;

            $nota->save();

            return; // ← sai aqui, não continua para a lógica normal
        }

        // ──────────────────────────────────────────────
        // PERÍODOS 1, 2, 3 — lógica normal
        // ──────────────────────────────────────────────

        $mac = isset($dados['mac'])
            ? (float) $dados['mac']
            : null;

        $npp = isset($dados['npp'])
            ? (float) $dados['npp']
            : null;

        $npt = isset($dados['npt'])
            ? (float) $dados['npt']
            : null;

        $faltas = (int) ($dados['faltas'] ?? 0);

        $nota = Nota::firstOrNew([
            'turma_aluno_id' => $turmaAlunoId,
            'turma_disciplina_professor_id' => $tdpId,
            'periodo' => $periodo,
        ]);

        $nota->mac = $mac;
        $nota->nota_prova_professor = $npp;
        $nota->nota_prova_trimestral = $npt;
        $nota->faltas = $faltas;

        $nota->media_trimestral = $this->calcularMediaTrimestral($mac, $npp, $npt);

        $nota->situacao_trimestral = $this->situacaoTrimestral(
            $nota->media_trimestral,
            $faltas
        );

        $nota->situacao_anual = null;

        $nota->save();
    }

    // ──────────────────────────────────────────────
    // RECÁLCULO FINAL
    // ──────────────────────────────────────────────

    private function recalcularFinal(
        string $turmaAlunoId,
        string $tdpId
    ): void {

        $notas = Nota::where('turma_aluno_id', $turmaAlunoId)
            ->where('turma_disciplina_professor_id', $tdpId)
            ->get()
            ->keyBy('periodo');

        // Só calcula após os 3 trimestres
        $temTresTrimestres = collect([1, 2, 3])
            ->every(
                fn($p) =>
                isset($notas[$p]) &&
                !is_null($notas[$p]->media_trimestral)
            );

        if (!$temTresTrimestres) {
            return;
        }

        // ──────────────────────────────────────────────
        // MÉDIA FINAL
        // ──────────────────────────────────────────────

        $mediaFinal = round((
            $notas[1]->media_trimestral +
            $notas[2]->media_trimestral +
            $notas[3]->media_trimestral
        ) / 3, 1);

        // ──────────────────────────────────────────────
        // SITUAÇÃO ANUAL DA DISCIPLINA
        // ──────────────────────────────────────────────

        $temEEF = $notas->contains(
            fn($n) => $n->situacao_trimestral === 'EEF'
        );

        $situacaoAnual = $this->situacaoAnual($mediaFinal, $temEEF);

        // ──────────────────────────────────────────────
        // RECURSO (PERÍODO 4)
        // ──────────────────────────────────────────────

        $mediaFinalEfectiva = $mediaFinal;

        if (
            isset($notas[4]) &&
            !is_null($notas[4]->media_trimestral)
        ) {
            $mediaRecurso = (float) $notas[4]->media_trimestral;

            $situacaoAnual = $mediaRecurso >= Nota::NOTA_MINIMA_APTO
                ? 'APTO'
                : 'N/APTO';

            // media_final passa a reflectir a nota do recurso
            $mediaFinalEfectiva = $mediaRecurso;
        }

        // ──────────────────────────────────────────────
        // ACTUALIZAR TODOS OS REGISTOS
        // ──────────────────────────────────────────────

        $notas->each(function ($nota) use ($mediaFinalEfectiva, $situacaoAnual) {

            $nota->media_final = $mediaFinalEfectiva;

            // situação anual apenas no período 3
            $nota->situacao_anual =
                $nota->periodo === 3
                ? $situacaoAnual
                : null;

            $nota->save();
        });
    }

    // ──────────────────────────────────────────────
    // MÉDIA TRIMESTRAL
    // ──────────────────────────────────────────────

    private function calcularMediaTrimestral(
        ?float $mac,
        ?float $npp,
        ?float $npt
    ): ?float {

        if (
            is_null($mac) ||
            is_null($npp) ||
            is_null($npt)
        ) {
            return null;
        }

        return ($mac + $npp + $npt) / 3;
    }

    // ──────────────────────────────────────────────
    // SITUAÇÃO TRIMESTRAL
    // ──────────────────────────────────────────────

    private function situacaoTrimestral(
        ?float $media,
        int $faltas
    ): ?string {

        if (is_null($media)) {
            return null;
        }

        return match (true) {

            $faltas >= Nota::FALTAS_EEF_TRIMESTRAL
            => 'EEF',

            $media >= Nota::NOTA_MINIMA_APTO
            => 'APTO',

            default
            => 'N/APTO',
        };
    }

    // ──────────────────────────────────────────────
    // SITUAÇÃO ANUAL DA DISCIPLINA
    // ──────────────────────────────────────────────

    private function situacaoAnual(
        float $mediaFinal,
        bool $temEEF
    ): string {

        if ($temEEF) {
            return 'EEF';
        }

        return $mediaFinal >= Nota::NOTA_MINIMA_APTO
            ? 'APTO'
            : 'N/APTO';
    }

    // ──────────────────────────────────────────────
    // CORRECÇÃO DE NOTA
    // ──────────────────────────────────────────────

    public function corrigirNota(
        Nota $nota,
        array $dados
    ): void {

        if (array_key_exists('mac', $dados)) {
            $nota->mac = $dados['mac'];
        }

        if (array_key_exists('npp', $dados)) {
            $nota->nota_prova_professor = $dados['npp'];
        }

        if (array_key_exists('npt', $dados)) {
            $nota->nota_prova_trimestral = $dados['npt'];
        }

        if (array_key_exists('faltas', $dados)) {
            $nota->faltas = $dados['faltas'];
        }

        // ── Média trimestral ──────────────────────
        $nota->media_trimestral =
            $this->calcularMediaTrimestral(
                $nota->mac,
                $nota->nota_prova_professor,
                $nota->nota_prova_trimestral
            );

        // ── Situação trimestral ───────────────────
        $nota->situacao_trimestral =
            $nota->periodo === 4
            ? null
            : $this->situacaoTrimestral(
                $nota->media_trimestral,
                $nota->faltas
            );

        $nota->situacao_anual = null;

        $nota->save();

        // Recalcular final
        $this->recalcularFinal(
            $nota->turma_aluno_id,
            $nota->turma_disciplina_professor_id
        );
    }

    // ──────────────────────────────────────────────
    // VERIFICAÇÃO RÁPIDA
    // ──────────────────────────────────────────────
    // Agora delega ao cérebro central
    // ──────────────────────────────────────────────

    public function verificarAprovacaoAluno(
        string $turmaAlunoId
    ): bool {

        $turmaAluno = TurmaAluno::with([
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])->findOrFail($turmaAlunoId);

        $resultado =
            $this->regraAcademicaService
                ->calcularResultadoFinalAluno($turmaAluno);

        return in_array(
            $resultado['resultado'],
            [
                'transita',
                'transita_com_deficiencia',
            ]
        );
    }
}