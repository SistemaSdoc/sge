<?php

namespace App\Http\Controllers\Tenant;

use App\Exports\MiniPautaExport;
use App\Helpers\ArredondamentoHelper;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ExportarMiniPautaController extends Controller
{
    /**
     * Exporta a Mini Pauta de UMA disciplina em Excel.
     *
     * Parâmetros de query aceites:
     *   - periodo (int, 1|2|3) - filtra apenas o trimestre pedido; omitir = todos
     */
    public function exportarDisciplina(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $periodo = (int) $request->query('periodo', 0);

        $cursoTutelado->loadMissing('instituicaoCurso.curso');

        $tdp = TurmaDisciplinaProfessor::where('turma_id', $turma->id)
            ->whereHas('classeTurnoDisciplina', fn ($q) => $q->where('id', $classeTurnoDisciplina->id))
            ->firstOrFail();

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
        ])
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->get();

        $alunos = $this->mapearAlunos($turmaAlunos, $periodo);

        $disciplinas = [
            [
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                'sigla' => mb_substr($tdp->classeTurnoDisciplina->disciplina->nome, 0, 6),
                'alunos' => $alunos,
            ],
        ];

        $export = new MiniPautaExport(
            disciplinas: $disciplinas,
            curso: $cursoTutelado->instituicaoCurso?->curso?->nome ?? '',
            turma: $turma->nome ?? '',
            anoLetivo: $turma->ano_lectivo ?? date('Y').'/'.(date('Y') + 1),
            instituicao: $instituicao->nome ?? 'INSTITUTO',
            sala: $turma->sala ?? '',
            classe: $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '',
        );

        $filename = 'mini_pauta_'.str($tdp->classeTurnoDisciplina->disciplina->nome)->slug().'.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Exporta a Mini Pauta de TODAS as disciplinas da turma.
     *
     * Parâmetros de query aceites:
     *   - periodo (int, 1|2|3) - filtra apenas o trimestre pedido; omitir = todos
     */
    public function exportarTurma(
        Request $request,
        Instituicao $instituicao,
        Turma $turma
    ) {
        $periodo = (int) $request->query('periodo', 0);

        $turma->loadMissing('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso');

        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        $disciplinas = $tdps->map(function ($tdp) use ($turma, $periodo) {
            $turmaAlunos = TurmaAluno::with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
                ->select('turma_aluno.*')
                ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
                ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
                ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
                ->where('turma_aluno.turma_id', $turma->id)
                ->where('turma_aluno.situacao', 'activo')
                ->where('turma_aluno.activo', true)
                ->orderBy('candidatos.nome')
                ->get();

            return [
                'nome' => $tdp->classeTurnoDisciplina?->disciplina?->nome,
                'sigla' => mb_strtoupper(mb_substr($tdp->classeTurnoDisciplina?->disciplina?->nome, 0, 6)),
                'alunos' => $this->mapearAlunos($turmaAlunos, $periodo),
            ];
        })->toArray();

        $export = new MiniPautaExport(
            disciplinas: $disciplinas,
            curso: $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome ?? '',
            turma: $turma->nome ?? '',
            anoLetivo: $turma->ano_lectivo ?? date('Y').'/'.(date('Y') + 1),
            instituicao: $instituicao->nome ?? 'INSTITUTO',
            sala: $turma->sala ?? '',
            classe: $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '',
        );

        $filename = 'mini_pauta_turma_'.str($turma->nome)->slug().'.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Mapeia os alunos para a estrutura esperada pelo exportador.
     *
     * @param  Collection<int, TurmaAluno>  $turmaAlunos
     * @return array<int, array<string, mixed>>
     */
    private function mapearAlunos(Collection $turmaAlunos, int $periodo): array
    {
        return $turmaAlunos->values()->map(function (TurmaAluno $ta, int $i) use ($periodo) {
            $notaSelecionada = $periodo > 0
                ? $ta->notas->firstWhere('periodo', $periodo)
                : null;

            $notasFiltradas = $periodo > 0
                ? collect([$notaSelecionada])->filter()
                : $ta->notas;

            $notasPorPeriodo = [];

            foreach ($notasFiltradas as $nota) {
                $notasPorPeriodo[$nota->periodo] = [
                    'mac' => $nota->mac,
                    'npp' => $nota->nota_prova_professor,
                    'npt' => $nota->nota_prova_trimestral,
                    'mt' => ArredondamentoHelper::roundToHalf($nota->media_trimestral),
                    'faltas' => $nota->faltas,
                    'situacao' => $nota->situacao,
                ];
            }

            $notaComFinal = $ta->notas->whereNotNull('media_final')->first();
            $mfd = $periodo === 0 && $notaComFinal
                ? ArredondamentoHelper::roundToHalf($notaComFinal->media_final)
                : null;

            $faltasParaResultado = $periodo > 0
                ? (int) ($notaSelecionada?->faltas ?? 0)
                : (int) $ta->notas->sum('faltas');

            $mediaParaResultado = $periodo > 0
                ? ($notaSelecionada?->media_trimestral !== null
                    ? ArredondamentoHelper::roundToHalf($notaSelecionada->media_trimestral)
                    : null)
                : $mfd;

            return [
                'numero' => $i + 1,
                'nome' => $ta->aluno->inscricao?->candidato?->nome ?? '',
                'notas' => $notasPorPeriodo,
                'mfd' => $mfd,
                'faltas_total' => $faltasParaResultado,
                'linha_vermelha' => $faltasParaResultado >= Nota::FALTAS_EEF_TRIMESTRAL,
                'resultado' => $this->calcularResultado($mediaParaResultado, $faltasParaResultado),
            ];
        })->toArray();
    }

    /**
     * Regra de aprovação da mini pauta.
     */
    protected function calcularResultado(?float $media, int $faltas): string
    {
        if ($faltas >= Nota::FALTAS_EEF_TRIMESTRAL) {
            return 'EEF';
        }

        if ($media === null) {
            return '';
        }

        return $media >= 10 ? 'APTO' : 'N/APTO';
    }
}
