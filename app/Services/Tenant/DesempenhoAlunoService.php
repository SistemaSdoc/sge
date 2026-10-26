<?php

namespace App\Services\Tenant;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DesempenhoAlunoService
{
    private const NOTA_MINIMA = 10;

    private const NOTA_MAXIMA = 20;

    private const PERIODOS = [1, 2, 3];

    private const LIMITE_FALTAS = 30;

    // ============================================================
    // Ponto de entrada
    // ============================================================

    public function gerar(Aluno $aluno, ?string $anoLectivoId = null): array
    {
        $matriculas = $this->matriculas($aluno);
        $anos = $this->anosLectivos($matriculas);
        $notas = $this->notasFinalizadas($matriculas);
        $atribuicoes = $this->atribuicoes($notas);

        $porAno = $this->analisarPorAno($matriculas, $notas, $atribuicoes);
        $anoActivo = $this->resolverAnoActivo($anos, $porAno, $anoLectivoId);
        $analise = $anoActivo ? $porAno->get($anoActivo) : null;

        $ehAnoMaisRecente = $anoActivo !== null
            && $anoActivo === (string) $anos->last()?->id;

        $indicadores = $this->gerarIndicadores($analise);

        return [
            'resumo' => $this->gerarResumo($indicadores),
            'indicadores' => $indicadores,
            'evolucao' => [
                'trimestres' => $analise['evolucao_trimestres'] ?? [],
                'media_anual' => $analise['media_geral'] ?? null,
                'anos' => $this->historico($anos, $porAno, $anoActivo),
            ],
            'disciplinas' => $analise['disciplinas'] ?? [],
            'assiduidade' => [
                'total' => $analise['faltas_total'] ?? 0,
                'limite' => self::LIMITE_FALTAS,
                'por_trimestre' => $analise['faltas_trimestre'] ?? [null, null, null],
            ],
            'projecao' => $this->gerarProjecao($analise),
            'balanco' => $ehAnoMaisRecente ? $this->gerarBalanco($analise) : null,
            'filtrosDisponiveis' => [
                'anosLectivos' => $anos->map(fn ($ano) => [
                    'id' => $ano->id,
                    'ano' => $ano->nome,
                    'activo' => (string) $ano->id === $anoActivo,
                ])->values()->all(),
            ],
            'filtrosActivos' => [
                'ano_lectivo_id' => $anoActivo,
                'ano_lectivo_nome' => $anos->first(fn ($a) => (string) $a->id === $anoActivo)?->nome,
            ],
        ];
    }

    // ============================================================
    // Carregamento de dados
    // ============================================================

    private function matriculas(Aluno $aluno): Collection
    {
        return TurmaAluno::where('aluno_id', $aluno->id)
            ->with('turma')
            ->get()
            ->filter(fn ($m) => $m->turma);
    }

    private function anosLectivos(Collection $matriculas): Collection
    {
        return AnoLectivo::whereIn('id', $matriculas->pluck('turma.ano_lectivo_id')->unique())
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }

    /**
     * Só entram notas de pautas finalizadas, agrupadas por matrícula.
     */
    private function notasFinalizadas(Collection $matriculas): Collection
    {
        $tabelaPauta = (new PautaStatus)->getTable();
        $tabelaNotas = (new Nota)->getTable();

        return Nota::whereIn('turma_aluno_id', $matriculas->pluck('id'))
            ->whereExists(function ($q) use ($tabelaPauta, $tabelaNotas) {
                $q->select(DB::raw(1))
                    ->from($tabelaPauta)
                    ->whereColumn("{$tabelaPauta}.turma_disciplina_professor_id", "{$tabelaNotas}.turma_disciplina_professor_id")
                    ->whereColumn("{$tabelaPauta}.periodo", "{$tabelaNotas}.periodo")
                    ->where("{$tabelaPauta}.status", 'finalizada');
            })
            ->get()
            ->groupBy('turma_aluno_id');
    }

    private function atribuicoes(Collection $notas): Collection
    {
        return TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->whereIn('id', $notas->flatten()->pluck('turma_disciplina_professor_id')->unique())
            ->get()
            ->keyBy('id');
    }

    // ============================================================
    // Análise por ano lectivo
    // ============================================================

    /**
     * Junta as notas de todas as matrículas do mesmo ano (ex.: mudança de turma).
     */
    private function analisarPorAno(Collection $matriculas, Collection $notas, Collection $atribuicoes): Collection
    {
        return $matriculas
            ->groupBy(fn ($m) => (string) $m->turma->ano_lectivo_id)
            ->map(function ($doAno) use ($notas, $atribuicoes) {
                $notasDoAno = $doAno->flatMap(fn ($m) => $notas->get($m->id, collect()));

                return $this->analisar($notasDoAno, $atribuicoes);
            });
    }

    private function analisar(Collection $notas, Collection $atribuicoes): array
    {
        $disciplinas = $notas
            ->groupBy('turma_disciplina_professor_id')
            ->map(function ($ns, $tdpId) use ($atribuicoes) {
                $porPeriodo = $ns->keyBy('periodo');

                $trimestres = collect(self::PERIODOS)
                    ->map(fn ($p) => $this->numero($porPeriodo->get($p)?->media_trimestral))
                    ->all();

                $faltas = collect(self::PERIODOS)
                    ->map(fn ($p) => $porPeriodo->has($p) ? (int) $porPeriodo->get($p)->faltas : null)
                    ->all();

                return [
                    'disciplina' => $atribuicoes->get($tdpId)?->classeTurnoDisciplina?->disciplina?->nome ?? '—',
                    'trimestres' => $trimestres,
                    'media' => $this->mediaDe($trimestres),
                    'faltas' => $faltas,
                ];
            })
            ->filter(fn ($d) => $d['media'] !== null)
            ->sortByDesc('media')
            ->values();

        $mediasTrimestre = $this->somaPorTrimestre($disciplinas, 'trimestres', fn ($vals) => $this->mediaDe($vals));
        $faltasTrimestre = $this->somaPorTrimestre($disciplinas, 'faltas', fn ($vals) => $vals->isNotEmpty() ? (int) $vals->sum() : null);

        return [
            'disciplinas' => $disciplinas->all(),
            'media_geral' => $this->mediaDe($mediasTrimestre),
            'evolucao_trimestres' => collect(self::PERIODOS)
                ->map(fn ($p, $i) => ['periodo' => $p, 'media' => $mediasTrimestre[$i]])
                ->all(),
            'faltas_total' => (int) collect($faltasTrimestre)->filter()->sum(),
            'faltas_trimestre' => $faltasTrimestre,
        ];
    }

    /**
     * Para cada trimestre, aplica $reduzir aos valores não nulos de todas as disciplinas.
     */
    private function somaPorTrimestre(Collection $disciplinas, string $campo, callable $reduzir): array
    {
        return collect(self::PERIODOS)->map(function ($p, $i) use ($disciplinas, $campo, $reduzir) {
            $valores = $disciplinas->pluck("{$campo}.{$i}")->filter(fn ($v) => $v !== null)->values();

            return $reduzir($valores);
        })->all();
    }

    private function resolverAnoActivo(Collection $anos, Collection $porAno, ?string $anoLectivoId): ?string
    {
        if ($anoLectivoId !== null && $porAno->has((string) $anoLectivoId)) {
            return (string) $anoLectivoId;
        }

        $ultimo = $anos->last()?->id;

        return $ultimo !== null ? (string) $ultimo : null;
    }

    private function historico(Collection $anos, Collection $porAno, ?string $anoActivo): array
    {
        return $anos->map(fn ($ano) => [
            'ano' => $ano->nome,
            'media' => $porAno->get((string) $ano->id)['media_geral'] ?? null,
            'activo' => (string) $ano->id === $anoActivo,
        ])->values()->all();
    }

    // ============================================================
    // Indicadores, resumo, projecção e balanço
    // ============================================================

    private function gerarIndicadores(?array $analise): array
    {
        $disciplinas = collect($analise['disciplinas'] ?? []);
        $total = $disciplinas->count();
        $positivas = $disciplinas->filter(fn ($d) => $d['media'] >= self::NOTA_MINIMA)->count();

        $medias = $this->mediasLancadas($analise);
        $variacao = $medias->count() >= 2 ? round($medias->last()['media'] - $medias->first()['media'], 1) : null;
        $melhor = $medias->sortByDesc('media')->first();

        return [
            'media_geral' => $analise['media_geral'] ?? null,
            'variacao' => $variacao,
            'positivas' => $positivas,
            'total_disciplinas' => $total,
            'em_risco' => $total - $positivas,
            'melhor_trimestre' => $melhor ? ['periodo' => $melhor['periodo'], 'media' => $melhor['media']] : null,
            'faltas' => $analise['faltas_total'] ?? 0,
            'limite_faltas' => self::LIMITE_FALTAS,
        ];
    }

    private function gerarResumo(array $ind): array
    {
        if ($ind['total_disciplinas'] === 0) {
            return [
                'tom' => 'neutro',
                'titulo' => 'Ainda não há notas finalizadas',
                'texto' => 'As notas aparecem aqui assim que as pautas de cada trimestre forem finalizadas.',
            ];
        }

        $media = $this->formatar($ind['media_geral']);
        $emRisco = $ind['em_risco'];
        $total = $ind['total_disciplinas'];

        if ($emRisco === 0) {
            if ($ind['variacao'] !== null && $ind['variacao'] < 0) {
                return [
                    'tom' => 'atencao',
                    'titulo' => 'A sua média desceu ao longo do ano',
                    'texto' => "Está positivo em todas as disciplinas, mas a média geral ({$media}) baixou desde o 1.º trimestre.",
                ];
            }

            return [
                'tom' => 'sucesso',
                'titulo' => 'Bom desempenho este ano',
                'texto' => "Está positivo em todas as disciplinas, com média geral de {$media}.",
            ];
        }

        return [
            'tom' => $emRisco * 2 >= $total ? 'risco' : 'atencao',
            'titulo' => $emRisco === 1
                ? 'Uma disciplina precisa de atenção'
                : "{$emRisco} disciplinas precisam de atenção",
            'texto' => "A média geral é {$media}, mas {$emRisco} de {$total} disciplinas estão abaixo de ".self::NOTA_MINIMA.'.',
        ];
    }

    /**
     * Nota necessária nos trimestres em falta para fechar cada disciplina em risco
     * com média 10 (média simples dos trimestres).
     */
    private function gerarProjecao(?array $analise): array
    {
        $disciplinas = collect($analise['disciplinas'] ?? []);
        $emFalta = count(self::PERIODOS) - $this->mediasLancadas($analise)->count();

        $vazia = ['trimestres_em_falta' => max(0, $emFalta), 'disciplinas' => []];

        if ($emFalta <= 0) {
            return $vazia;
        }

        $vazia['disciplinas'] = $disciplinas
            ->filter(fn ($d) => $d['media'] < self::NOTA_MINIMA)
            ->map(function ($d) {
                $lancadas = collect($d['trimestres'])->filter(fn ($v) => $v !== null);
                $faltam = count(self::PERIODOS) - $lancadas->count();

                if ($faltam <= 0) {
                    return null;
                }

                $necessaria = ceil(((self::NOTA_MINIMA * count(self::PERIODOS)) - $lancadas->sum()) / $faltam * 10) / 10;

                return [
                    'disciplina' => $d['disciplina'],
                    'media_actual' => $d['media'],
                    'nota_necessaria' => max(0, $necessaria),
                    'possivel' => $necessaria <= self::NOTA_MAXIMA,
                ];
            })
            ->filter()
            ->sortByDesc('nota_necessaria')
            ->values()
            ->all();

        return $vazia;
    }

    /**
     * Aviso do cabeçalho. Devolve null quando não há motivo de alerta.
     */
    private function gerarBalanco(?array $analise): ?array
    {
        if (! $analise || empty($analise['disciplinas'])) {
            return null;
        }

        $emRisco = collect($analise['disciplinas'])
            ->filter(fn ($d) => $d['media'] < self::NOTA_MINIMA)
            ->sortBy('media')
            ->pluck('disciplina');

        $medias = $this->mediasLancadas($analise);
        $aDescer = $medias->count() >= 2 && $medias->last()['media'] < $medias->first()['media'];

        if ($emRisco->isEmpty() && ! $aDescer) {
            return null;
        }

        $media = $this->formatar($analise['media_geral']);
        $limite = self::NOTA_MINIMA;

        if ($emRisco->isEmpty()) {
            return [
                'titulo' => 'A sua média desceu ao longo do ano',
                'texto' => "Faça o balanço das suas notas: a média geral é {$media}, mas desceu desde o 1.º trimestre. Estude mais e reveja a matéria com regularidade para recuperar.",
            ];
        }

        $quando = $medias->count() < count(self::PERIODOS)
            ? 'no próximo trimestre'
            : 'para recuperar as disciplinas em risco';
        $lista = $emRisco->join(', ', ' e ');
        $verbo = $emRisco->count() > 1 ? 'estão' : 'está';

        return [
            'titulo' => "Precisa de se esforçar mais {$quando}",
            'texto' => "Faça o balanço das suas notas: a média geral é {$media} e {$lista} {$verbo} abaixo de {$limite}. Estude mais, reveja a matéria todas as semanas e tire as dúvidas com o professor antes dos testes.",
        ];
    }

    // ============================================================
    // Auxiliares
    // ============================================================

    /**
     * Trimestres com média já calculada, por ordem: [['periodo' => 1, 'media' => 12.8], ...]
     */
    private function mediasLancadas(?array $analise): Collection
    {
        return collect($analise['evolucao_trimestres'] ?? [])
            ->filter(fn ($t) => $t['media'] !== null)
            ->values();
    }

    private function mediaDe(iterable $valores): ?float
    {
        $v = collect($valores)->filter(fn ($x) => $x !== null);

        return $v->isNotEmpty() ? round($v->avg(), 1) : null;
    }

    private function numero($valor): ?float
    {
        return $valor === null ? null : round((float) $valor, 1);
    }

    private function formatar(?float $valor): string
    {
        return $valor === null ? '—' : number_format($valor, 1, ',', '');
    }
}    