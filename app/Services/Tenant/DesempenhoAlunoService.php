<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DesempenhoAlunoService
{
    private const NOTA_MINIMA = 10;
    private const PERIODOS = [1, 2, 3];
    private const LIMITE_FALTAS = 30;

    public function gerar(Aluno $aluno, ?string $anoLectivoId = null): array
    {
        $matriculas = TurmaAluno::where('aluno_id', $aluno->id)
            ->with('turma')
            ->get()
            ->filter(fn ($m) => $m->turma);

        $anos = AnoLectivo::whereIn('id', $matriculas->pluck('turma.ano_lectivo_id')->unique())
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $notas = Nota::whereIn('turma_aluno_id', $matriculas->pluck('id'))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from((new PautaStatus)->getTable())
                    ->whereColumn('pauta_status.turma_disciplina_professor_id', 'notas.turma_disciplina_professor_id')
                    ->whereColumn('pauta_status.periodo', 'notas.periodo')
                    ->where('pauta_status.status', 'finalizada');
            })
            ->get()
            ->groupBy('turma_aluno_id');

        $atribuicoes = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->whereIn('id', $notas->flatten()->pluck('turma_disciplina_professor_id')->unique())
            ->get()
            ->keyBy('id');

        // Análise por ano
        $porAno = $matriculas->map(function ($m) use ($notas, $atribuicoes) {
            return ['ano_lectivo_id' => $m->turma->ano_lectivo_id]
                + $this->analisar($notas->get($m->id, collect()), $atribuicoes);
        })->keyBy('ano_lectivo_id');

        // Ano activo
        $anoActivo = $anoLectivoId && $porAno->has($anoLectivoId)
            ? $anoLectivoId
            : $anos->last()?->id;

        $a = $anoActivo ? $porAno->get($anoActivo) : null;
        $disciplinas = collect($a['disciplinas'] ?? []);

        // Histórico por ano
        $historico = $anos->map(fn ($ano) => [
            'ano' => $ano->nome,
            'media' => $porAno->get($ano->id)['media_geral'] ?? null,
            'activo' => $ano->id === $anoActivo,
        ])->values()->all();

        return [
            'evolucao' => [
                'trimestres' => $a['evolucao_trimestres'] ?? [],
                'anos' => $historico,
            ],
            'disciplinas' => $disciplinas->values()->all(),
            'assiduidade' => [
                'total' => $a['faltas_total'] ?? 0,
                'limite' => self::LIMITE_FALTAS,
                'por_trimestre' => $a['faltas_trimestre'] ?? [null, null, null],
            ],
            'filtrosDisponiveis' => [
                'anosLectivos' => $anos->map(fn ($x) => [
                    'id' => $x->id,
                    'ano' => $x->nome,
                    'activo' => $x->id === $anoActivo,
                ])->values()->all(),
            ],
            'filtrosActivos' => [
                'ano_lectivo_id' => $anoActivo,
                'ano_lectivo_nome' => $anos->firstWhere('id', $anoActivo)?->nome,
            ],
        ];
    }

    // ------------------------------------------------------------
    // Análise de um ano lectivo
    // ------------------------------------------------------------
    private function analisar(Collection $notas, Collection $atribuicoes): array
    {
        $disciplinas = $notas
            ->groupBy('turma_disciplina_professor_id')
            ->map(function ($ns, $tdpId) use ($atribuicoes) {
                $porPeriodo = $ns->keyBy('periodo');

                $trimestres = collect(self::PERIODOS)
                    ->map(fn ($p) => $this->numero($porPeriodo->get($p)?->media_trimestral))
                    ->all();

                $lancadas = array_values(array_filter($trimestres, fn ($v) => $v !== null));
                $media = $lancadas ? round(array_sum($lancadas) / count($lancadas), 1) : null;

                $faltas = collect(self::PERIODOS)
                    ->map(fn ($p) => $porPeriodo->has($p) ? (int) $porPeriodo->get($p)->faltas : null)
                    ->all();

                return [
                    'disciplina' => $atribuicoes->get($tdpId)?->classeTurnoDisciplina?->disciplina?->nome ?? '—',
                    'trimestres' => $trimestres,
                    'media' => $media,
                    'faltas' => $faltas,
                ];
            })
            ->filter(fn ($d) => $d['media'] !== null)
            ->sortByDesc('media')
            ->values();

        // Média geral por trimestre
        $mediasTrimestre = collect(self::PERIODOS)->map(function ($p, $i) use ($disciplinas) {
            $vals = $disciplinas->pluck("trimestres.$i")->filter(fn ($v) => $v !== null);
            return $vals->isNotEmpty() ? round($vals->avg(), 1) : null;
        })->all();

        $lancadas = array_values(array_filter($mediasTrimestre, fn ($v) => $v !== null));

        $evolucao = collect(self::PERIODOS)->map(fn ($p, $i) => [
            'periodo' => $p,
            'media' => $mediasTrimestre[$i],
        ])->all();

        // Faltas por trimestre
        $faltasTrimestre = collect(self::PERIODOS)->map(function ($p, $i) use ($disciplinas) {
            $vals = $disciplinas->pluck("faltas.$i")->filter(fn ($v) => $v !== null);
            return $vals->isNotEmpty() ? (int) $vals->sum() : null;
        })->all();

        return [
            'disciplinas' => $disciplinas->all(),
            'media_geral' => $lancadas ? round(array_sum($lancadas) / count($lancadas), 1) : null,
            'evolucao_trimestres' => $evolucao,
            'faltas_total' => (int) collect($faltasTrimestre)->filter()->sum(),
            'faltas_trimestre' => $faltasTrimestre,
        ];
    }

    private function numero($valor): ?float
    {
        return $valor === null ? null : round((float) $valor, 1);
    }
}