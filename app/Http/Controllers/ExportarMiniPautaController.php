<?php

namespace App\Http\Controllers;

use App\Exports\MiniPautaExport;
use App\Models\ClasseTurnoDisciplina;
use App\Models\InstituicaoCurso;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\CursoTutelado;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\Disciplina;
use App\Models\TurmaDisciplinaProfessor;
use App\Models\TurmaAluno;
use Maatwebsite\Excel\Facades\Excel;

class ExportarMiniPautaController extends Controller
{
    /**
     * Exporta a Mini Pauta de UMA disciplina em Excel.
     */
    public function exportarDisciplina(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        // ALTERADO: Buscar via classe_turno_disciplina_id
        $tdp = TurmaDisciplinaProfessor::where('turma_id', $turma->id)
            ->whereHas('classeTurnoDisciplina', fn($q) => $q->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id))
            ->firstOrFail();


        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->get();

        $alunos = $turmaAlunos->values()->map(function ($ta, $i) {

            $notasPorPeriodo = [];

            foreach ($ta->notas as $nota) {
                $notasPorPeriodo[$nota->periodo] = [
                    'mac' => $nota->mac,
                    'npp' => $nota->nota_prova_professor,
                    'npt' => $nota->nota_prova_trimestral,
                    'mt' => $nota->media_trimestral,
                    'faltas' => $nota->faltas,
                    'situacao' => $nota->situacao,
                ];
            }

            $notaComFinal = $ta->notas->whereNotNull('media_final')->first();
            $mfd = $notaComFinal?->media_final;
            $faltasTotal = $ta->notas->sum('faltas');

            // cálculo correto para mini pauta
            $resultado = $this->calcularResultado($mfd, $faltasTotal);

            return [
                'numero' => $i + 1,
                'nome' => $ta->aluno->inscricao?->candidato?->nome ?? '',
                'notas' => $notasPorPeriodo,
                'mfd' => $mfd,
                'faltas_total' => $faltasTotal,
                'resultado' => $resultado,
            ];
        })->toArray();

        $disciplinas = [
            [
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                'sigla' => mb_substr($tdp->classeTurnoDisciplina->disciplina->nome, 0, 6),
                'alunos' => $alunos,
            ]
        ];

        $export = new MiniPautaExport(
            disciplinas: $disciplinas,
            curso: $instituicaoCurso->curso->nome ?? '',
            turma: $turma->nome ?? '',
            anoLetivo: $turma->ano_letivo ?? date('Y') . '/' . (date('Y') + 1),
            instituicao: $instituicao->nome ?? 'INSTITUTO',
            sala: $turma->sala ?? '',
            classe: $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '',
        );

        $filename = 'mini_pauta_' . str($tdp->classeTurnoDisciplina->disciplina->nome)->slug() . '.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Exporta a Mini Pauta de TODAS as disciplinas da turma.
     */
    public function exportarTurma(
        Instituicao $instituicao,
        InstituicaoCurso $instituicaoCurso,
        Turma $turma
    ) {
        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        $disciplinas = $tdps->map(function ($tdp) use ($turma) {

            $turmaAlunos = TurmaAluno::with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
                ->where('turma_id', $turma->id)
                ->where('situacao', 'activo')
                ->where('activo', true)
                ->get();

            $alunos = $turmaAlunos->values()->map(function ($ta, $i) {

                $notasPorPeriodo = [];

                foreach ($ta->notas as $nota) {
                    $notasPorPeriodo[$nota->periodo] = [
                        'mac' => $nota->mac,
                        'npp' => $nota->nota_prova_professor,
                        'npt' => $nota->nota_prova_trimestral,
                        'mt' => $nota->media_trimestral,
                        'faltas' => $nota->faltas,
                        'situacao' => $nota->situacao,
                    ];
                }

                $notaComFinal = $ta->notas->whereNotNull('media_final')->first();
                $mfd = $notaComFinal?->media_final;
                $faltasTotal = $ta->notas->sum('faltas');

                // cálculo correto
                $resultado = $this->calcularResultado($mfd, $faltasTotal);

                return [
                    'numero' => $i + 1,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome ?? '',
                    'notas' => $notasPorPeriodo,
                    'mfd' => $mfd,
                    'faltas_total' => $faltasTotal,
                    'resultado' => $resultado,
                ];
            })->toArray();

            return [
                'nome' => $tdp->classeTurnoDisciplina?->disciplina?->nome,
                'sigla' => mb_strtoupper(mb_substr($tdp->classeTurnoDisciplina?->disciplina?->nome, 0, 6)),
                'alunos' => $alunos,
            ];
        })->toArray();

        $export = new MiniPautaExport(
            disciplinas: $disciplinas,
            curso: $instituicaoCurso->curso->nome ?? '',
            turma: $turma->nome ?? '',
            anoLetivo: $turma->ano_letivo ?? date('Y') . '/' . (date('Y') + 1),
            instituicao: $instituicao->nome ?? 'INSTITUTO',
            sala: $turma->sala ?? '',
            classe: $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '',
        );

        $filename = 'mini_pauta_turma_' . str($turma->nome)->slug() . '.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Regra de aprovação (mini pauta - por disciplina)
     */
    protected function calcularResultado(?float $mfd, int $faltas): string
    {
        if ($faltas >= 10) {
            return 'N/APTO';
        }

        if ($mfd === null) {
            return '';
        }

        return $mfd >= 10 ? 'APTO' : 'N/APTO';
    }
}
