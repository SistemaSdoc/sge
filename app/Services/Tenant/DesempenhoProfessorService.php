<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Nota;
use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\Professor;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;

class DesempenhoProfessorService
{
    private const NOTA_COLUNA_VALOR = 'media_trimestral';
    private const NOTA_COLUNA_PERIODO = 'periodo';
    private const NOTA_MINIMA_APROVACAO = 10; // escala 0-20

    private const PERIODOS = [1, 2, 3];

    /** Variações aceites para cada resultado em turma_aluno.resultado_academico */
    private const RESULTADOS_APROVADO = ['aprovado', 'aprovada'];
    private const RESULTADOS_REPROVADO = ['reprovado', 'reprovada'];
    private const RESULTADOS_EXCLUIDO = ['excluido_falta', 'excluido_faltas', 'excluido_por_faltas', 'excluido', 'excluida'];

    /**
     * Devolve resumo, resultados, linhas da tabela e PAP do professor.
     */
    public function gerar(Professor $professor, ?string $anoLectivoId = null, ?string $periodo = null): array
    {
        $atribuicoes = $this->atribuicoes($professor, $anoLectivoId);

        $atribuicaoIds = $atribuicoes->pluck('id');
        $turmaIds = $atribuicoes->pluck('turma_id')->unique()->values();

        $alunosPorTurma = $this->alunosPorTurma($turmaIds);
        $mediasPorAluno = $this->mediasPorAluno($atribuicaoIds, $periodo);
        $pautas = $this->pautas($atribuicaoIds);

        $totalAvaliados = 0;
        $totalAprovados = 0;
        $pautasFinalizadas = 0;

        $turmas = $atribuicoes->map(function ($a) use (
            $alunosPorTurma, $mediasPorAluno, $pautas, $periodo,
            &$totalAvaliados, &$totalAprovados, &$pautasFinalizadas
        ) {
            $medias = $mediasPorAluno->get($a->id, collect());
            $avaliados = $medias->count();
            $aprovados = $medias->filter(fn ($m) => (float) $m->media >= self::NOTA_MINIMA_APROVACAO)->count();

            $totalAvaliados += $avaliados;
            $totalAprovados += $aprovados;

            $estados = $this->estadosDasPautas($pautas->get($a->id, collect()));
            $pautasFinalizadas += $this->contarFinalizadas($estados, $periodo);

            return [
                'turma' => $a->turma?->nome,
                'disciplina' => $a->classeTurnoDisciplina?->disciplina?->nome,
                'total_alunos' => (int) ($alunosPorTurma[$a->turma_id] ?? 0),
                'avaliados' => $avaliados,
                'aprovados' => $aprovados,
                'reprovados' => $avaliados - $aprovados,
                'media' => $avaliados ? round($medias->avg('media'), 1) : null,
                'aprovacao' => $avaliados ? round($aprovados / $avaliados * 100) : 0,
                'pautas' => $estados,
            ];
        })->sortBy([['turma', 'asc'], ['disciplina', 'asc']])->values();

        return [
            'resumo' => [
                'total_turmas' => $turmaIds->count(),
                'total_alunos' => (int) $alunosPorTurma->sum(),
                'taxa_aprovacao' => $totalAvaliados ? round($totalAprovados / $totalAvaliados * 100) : 0,
                'pautas_finalizadas' => $pautasFinalizadas,
            ],
            'resultados' => $this->resultados($turmaIds, $atribuicoes, $mediasPorAluno),
            'turmas' => $turmas,
            'pap' => $this->pap($professor, $anoLectivoId),
        ];
    }

    public function filtrosDisponiveis(): array
    {
        return [
            'anosLectivos' => AnoLectivo::orderByDesc('data_inicio')
                ->get(['id', 'nome'])
                ->map(fn ($a) => ['id' => $a->id, 'ano' => $a->nome]),
            'periodos' => self::PERIODOS,
        ];
    }

    // ------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------

    /** Conexão do tenant (a mesma usada pelos models do tenant). */
    private function conexao(): \Illuminate\Database\ConnectionInterface
    {
        return (new Nota)->getConnection();
    }

    private function atribuicoes(Professor $professor, ?string $anoLectivoId): Collection
    {
        return TurmaDisciplinaProfessor::query()
            ->where('professor_id', $professor->id)
            ->whereHas('turma', function ($q) use ($anoLectivoId) {
                if ($anoLectivoId) {
                    $q->where('ano_lectivo_id', $anoLectivoId);
                }
            })
            ->with(['turma', 'classeTurnoDisciplina.disciplina'])
            ->get();
    }

    private function alunosPorTurma(Collection $turmaIds): Collection
    {
        if ($turmaIds->isEmpty()) {
            return collect();
        }

        return $this->conexao()->table('turma_aluno')
            ->whereIn('turma_id', $turmaIds)
            ->where('activo', true)
            ->selectRaw('turma_id, COUNT(DISTINCT aluno_id) as total')
            ->groupBy('turma_id')
            ->pluck('total', 'turma_id');
    }

    /**
     * Média de cada aluno por atribuição (turma + disciplina + professor).
     * Aceita a nota se NÃO for rascunho OU se a pauta desse período estiver finalizada.
     * Marca o aluno como excluído se alguma nota tiver situação EEF.
     */
    private function mediasPorAluno(Collection $atribuicaoIds, ?string $periodo): Collection
    {
        if ($atribuicaoIds->isEmpty()) {
            return collect();
        }

        $finalizadas = PautaStatus::query()
            ->whereIn('turma_disciplina_professor_id', $atribuicaoIds)
            ->where('status', 'finalizada')
            ->get(['turma_disciplina_professor_id', 'periodo'])
            ->mapWithKeys(fn ($p) => [$p->turma_disciplina_professor_id . '|' . $p->periodo => true]);

        return Nota::query()
            ->whereIn('turma_disciplina_professor_id', $atribuicaoIds)
            ->whereNotNull(self::NOTA_COLUNA_VALOR)
            ->when($periodo, fn ($q) => $q->where(self::NOTA_COLUNA_PERIODO, $periodo))
            ->get()
            ->filter(fn ($n) => ! $n->is_rascunho
                || $finalizadas->has($n->turma_disciplina_professor_id . '|' . $n->periodo))
            ->groupBy(fn ($n) => $n->turma_disciplina_professor_id . '|' . $n->turma_aluno_id)
            ->map(function ($grupo) {
                $primeira = $grupo->first();

                return (object) [
                    'turma_disciplina_professor_id' => $primeira->turma_disciplina_professor_id,
                    'turma_aluno_id' => $primeira->turma_aluno_id,
                    'media' => (float) $grupo->avg(self::NOTA_COLUNA_VALOR),
                    'excluido' => $grupo->contains(
                        fn ($n) => $n->situacao_trimestral === 'EEF' || $n->situacao_anual === 'EEF'
                    ),
                ];
            })
            ->values()
            ->groupBy('turma_disciplina_professor_id');
    }

    private function pautas(Collection $atribuicaoIds): Collection
    {
        if ($atribuicaoIds->isEmpty()) {
            return collect();
        }

        return PautaStatus::query()
            ->whereIn('turma_disciplina_professor_id', $atribuicaoIds)
            ->get()
            ->groupBy('turma_disciplina_professor_id');
    }

    // ------------------------------------------------------------
    // Resultados (aprovados / reprovados / excluídos)
    // ------------------------------------------------------------

    /**
     * Fonte principal: turma_aluno.resultado_academico (fecho do ano).
     * Se a coluna não existir ou ainda não estiver preenchida, usa como
     * alternativa as médias das notas (>= NOTA_MINIMA_APROVACAO) e a situação EEF.
     */
    private function resultados(Collection $turmaIds, Collection $atribuicoes, Collection $mediasPorAluno): array
    {
        if ($turmaIds->isEmpty()) {
            return $this->resultadosVazios();
        }

        $oficial = $this->resultadosOficiais($turmaIds);

        if ($oficial !== null && ($oficial['aprovados'] + $oficial['reprovados'] + $oficial['excluidos']) > 0) {
            return $oficial;
        }

        return $this->resultadosPorMedias($atribuicoes, $mediasPorAluno);
    }

    private function resultadosVazios(string $fonte = 'nenhuma'): array
    {
        return [
            'total' => 0,
            'aprovados' => 0,
            'reprovados' => 0,
            'excluidos' => 0,
            'pendentes' => 0,
            'fonte' => $fonte,
            'por_turma' => [],
        ];
    }

    /** Devolve null se a coluna resultado_academico não existir. */
    private function resultadosOficiais(Collection $turmaIds): ?array
    {
        $conexao = $this->conexao();

        if (! $conexao->getSchemaBuilder()->hasColumn('turma_aluno', 'resultado_academico')) {
            return null;
        }

        $linhas = $conexao->table('turma_aluno')
            ->join('turmas', 'turmas.id', '=', 'turma_aluno.turma_id')
            ->whereIn('turma_aluno.turma_id', $turmaIds)
            ->selectRaw('turma_aluno.turma_id as turma_id, turmas.nome as turma, turma_aluno.resultado_academico as resultado, COUNT(*) as total')
            ->groupBy('turma_aluno.turma_id', 'turmas.nome', 'turma_aluno.resultado_academico')
            ->get();

        if ($linhas->isEmpty()) {
            return null;
        }

        // Normaliza o texto do resultado (minúsculas, sem espaços, "-" → "_")
        $normalizar = fn ($r) => str_replace([' ', '-'], '_', strtolower(trim((string) $r)));

        $conta = function (Collection $c, array $validos) use ($normalizar): int {
            return (int) $c
                ->filter(fn ($l) => in_array($normalizar($l->resultado), $validos, true))
                ->sum('total');
        };

        $pendentes = (int) $linhas
            ->filter(fn ($l) => ! in_array(
                $normalizar($l->resultado),
                array_merge(self::RESULTADOS_APROVADO, self::RESULTADOS_REPROVADO, self::RESULTADOS_EXCLUIDO),
                true
            ))
            ->sum('total');

        $aprovados = $conta($linhas, self::RESULTADOS_APROVADO);
        $reprovados = $conta($linhas, self::RESULTADOS_REPROVADO);
        $excluidos = $conta($linhas, self::RESULTADOS_EXCLUIDO);

        $porTurma = $linhas->groupBy('turma_id')->map(function (Collection $g) use ($conta) {
            return [
                'turma' => $g->first()->turma,
                'aprovados' => $conta($g, self::RESULTADOS_APROVADO),
                'reprovados' => $conta($g, self::RESULTADOS_REPROVADO),
                'excluidos' => $conta($g, self::RESULTADOS_EXCLUIDO),
            ];
        })->sortBy('turma')->values()->all();

        return [
            // total = só alunos com resultado definido, para as percentagens fecharem em 100%
            'total' => $aprovados + $reprovados + $excluidos,
            'aprovados' => $aprovados,
            'reprovados' => $reprovados,
            'excluidos' => $excluidos,
            'pendentes' => $pendentes,
            'fonte' => 'resultado_academico',
            'por_turma' => $porTurma,
        ];
    }

    /**
     * Alternativa: aluno excluído se tiver situação EEF; senão aprovado se a média
     * das suas notas (nas disciplinas do professor) >= mínimo; senão reprovado.
     */
    private function resultadosPorMedias(Collection $atribuicoes, Collection $mediasPorAluno): array
    {
        $alunos = []; // [turma_id][turma_aluno_id] => ['medias' => [], 'excluido' => bool]
        $nomes = [];

        foreach ($atribuicoes as $a) {
            $nomes[$a->turma_id] = $a->turma?->nome ?? '—';

            foreach ($mediasPorAluno->get($a->id, collect()) as $m) {
                $atual = $alunos[$a->turma_id][$m->turma_aluno_id] ?? ['medias' => [], 'excluido' => false];
                $atual['medias'][] = (float) $m->media;
                $atual['excluido'] = $atual['excluido'] || (bool) $m->excluido;
                $alunos[$a->turma_id][$m->turma_aluno_id] = $atual;
            }
        }

        if (empty($alunos)) {
            return $this->resultadosVazios('medias');
        }

        $totais = ['aprovados' => 0, 'reprovados' => 0, 'excluidos' => 0];
        $porTurma = [];

        foreach ($alunos as $turmaId => $lista) {
            $linha = ['turma' => $nomes[$turmaId], 'aprovados' => 0, 'reprovados' => 0, 'excluidos' => 0];

            foreach ($lista as $aluno) {
                $media = array_sum($aluno['medias']) / count($aluno['medias']);

                if ($aluno['excluido']) {
                    $linha['excluidos']++;
                } elseif ($media >= self::NOTA_MINIMA_APROVACAO) {
                    $linha['aprovados']++;
                } else {
                    $linha['reprovados']++;
                }
            }

            foreach (array_keys($totais) as $k) {
                $totais[$k] += $linha[$k];
            }

            $porTurma[] = $linha;
        }

        usort($porTurma, fn ($x, $y) => strcmp($x['turma'], $y['turma']));

        return [
            'total' => array_sum($totais),
            'aprovados' => $totais['aprovados'],
            'reprovados' => $totais['reprovados'],
            'excluidos' => $totais['excluidos'],
            'pendentes' => 0,
            'fonte' => 'medias',
            'por_turma' => $porTurma,
        ];
    }

    private function pap(Professor $professor, ?string $anoLectivoId): array
    {
        $grupos = $professor->gruposPap()
            ->when($anoLectivoId, fn ($q) => $q->whereHas(
                'turma',
                fn ($t) => $t->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->get(['id', 'status_aprovacao']);

        return [
            'total' => $grupos->count(),
            'por_estado' => $grupos->countBy('status_aprovacao'),
        ];
    }

    // ------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------

    /** Devolve o estado das pautas do 1º, 2º e 3º trimestre. */
    private function estadosDasPautas(Collection $pautasDaAtribuicao): array
    {
        $porPeriodo = $pautasDaAtribuicao->keyBy('periodo');

        return collect(self::PERIODOS)
            ->map(fn ($p) => $porPeriodo->get($p)?->status ?? 'por_iniciar')
            ->all();
    }

    private function contarFinalizadas(array $estados, ?string $periodo): int
    {
        if ($periodo) {
            return (int) (($estados[(int) $periodo - 1] ?? null) === 'finalizada');
        }

        return count(array_filter($estados, fn ($s) => $s === 'finalizada'));
    }
}