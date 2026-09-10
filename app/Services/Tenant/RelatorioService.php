<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;

class RelatorioService
{
    public function gerar(array $filtros): array
    {
        return match ($filtros['tipo'] ?? 'geral') {
            'alunos' => $this->dadosAlunos($filtros),
            'professores' => $this->dadosProfessores($filtros),
            'turmas' => $this->dadosTurmas($filtros),
            'pap' => $this->dadosPap($filtros),
            'financeiro' => $this->dadosFinanceiro($filtros),
            default => $this->resumoGeral($filtros),
        };
    }

    protected function anoLectivo(array $filtros): ?AnoLectivo
    {
        if (! empty($filtros['ano_lectivo_id'])) {
            return AnoLectivo::find($filtros['ano_lectivo_id']);
        }

        return AnoLectivo::activo();
    }

    // ============================================
    // GERAL
    // ============================================
    protected function resumoGeral(array $filtros): array
    {
        $ano = $this->anoLectivo($filtros);

        $totalAlunos = Aluno::doAnoLectivoActivo()->count();
        $totalTurmas = Turma::when($ano, fn ($q) => $q->where('ano_lectivo_id', $ano->id))->count();
        $totalProfessores = Professor::count();
        $totalDisciplinas = Disciplina::count();

        $porClasse = Turma::when($ano, fn ($q) => $q->where('ano_lectivo_id', $ano->id))
            ->with('cursoClasseTurno.cursoClasse.classe')
            ->withCount('alunosActivos')
            ->get()
            ->groupBy(fn ($t) => $t->cursoClasseTurno?->cursoClasse?->classe?->nome ?? 'Sem classe')
            ->map(fn ($turmas, $classe) => [
                'classe' => $classe,
                'matriculados' => $turmas->sum('alunos_activos_count'),
                'vagas' => $turmas->sum('max_alunos'),
            ])
            ->values();

        $totalFinalistasPap = ElementoGrupoPap::count();

        $porEstadoPap = GrupoPap::get()
            ->groupBy(fn ($g) => $g->status_aprovacao ?: 'pendente')
            ->map(fn ($grupo, $estado) => [
                'label' => ucfirst($estado),
                'valor' => $grupo->count(),
            ])
            ->values();

        $porEspecialidade = Professor::get()
            ->groupBy(fn ($p) => $p->especialidade ?: 'Não definida')
            ->map(fn ($grupo, $especialidade) => [
                'label' => $especialidade,
                'valor' => $grupo->count(),
            ])
            ->values();

        return [
            'stats' => [
                ['label' => 'Alunos', 'valor' => $totalAlunos],
                ['label' => 'Turmas', 'valor' => $totalTurmas],
                ['label' => 'Professores', 'valor' => $totalProfessores],
                ['label' => 'Disciplinas', 'valor' => $totalDisciplinas],
            ],
            'tabela' => $porClasse,

            // Dados já no formato que o KpiChartCard.jsx espera (label/valor) —
            // um array por card do dashboard.
            'graficos' => [
                'alunos_por_classe' => $porClasse->map(fn ($c) => [
                    'label' => $c['classe'],
                    'valor' => $c['matriculados'],
                ])->values(),

                'professores_por_especialidade' => $porEspecialidade,

                'turmas_ocupacao' => $porClasse->map(fn ($c) => [
                    'label' => $c['classe'],
                    'valor' => $c['vagas'] ? round($c['matriculados'] / $c['vagas'] * 100) : 0,
                ])->values(),

                'pap_por_estado' => $porEstadoPap,

                'pagamentos_mensal' => $this->pagamentosMensal(),

                // Sem model/tabela de Documentos ainda — array vazio até existir a fonte real.
                'documentos_por_tipo' => [],
            ],

            'kpis_extra' => [
                'pap_finalistas' => $totalFinalistasPap,

                // "Adimplência" depende de ItemPagavel + PagamentoPeriodo, que ainda
                // não estão implementados (mesma observação já deixada em dadosFinanceiro()).
                'pagamentos_adimplencia' => null,

                'documentos_pendentes' => null,
            ],
        ];
    }

    /**
     * Total arrecadado por mês nos últimos 6 meses (incluindo meses sem pagamentos,
     * preenchidos com 0), para o mini-gráfico de linha do card "Pagamentos".
     */
    protected function pagamentosMensal(): array
    {
        $mesesPt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $periodo = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $totaisPorMes = Pagamento::where('data_pagamento', '>=', $periodo->first())
            ->get()
            ->groupBy(fn ($p) => $p->data_pagamento->format('Y-m'))
            ->map(fn ($grupo) => (float) $grupo->sum('valor_total'));

        return $periodo->map(fn ($data) => [
            'label' => $mesesPt[$data->month - 1],
            'valor' => $totaisPorMes->get($data->format('Y-m'), 0),
        ])->values()->all();
    }

    // ============================================
    // ALUNOS
    // ============================================
    protected function dadosAlunos(array $filtros): array
    {
        $ano = $this->anoLectivo($filtros);

        $query = Aluno::query()
            ->with(['user', 'turmaActual.cursoClasseTurno.cursoClasse.classe'])
            ->when($ano, fn ($q) => $q->doAnoLectivo($ano->id))
            ->when($filtros['pesquisa'] ?? null, function ($q, $termo) {
                $q->where(function ($q2) use ($termo) {
                    $q2->where('matricula', 'like', "%{$termo}%")
                        ->orWhere('numero_processo', 'like', "%{$termo}%")
                       ->orWhereHas('user', fn ($q3) => $q3->where('nome', 'like', "%{$termo}%"));
                });
            })
            ->when($filtros['turma_id'] ?? null, function ($q, $turmaId) {
                $q->whereHas('turmas', fn ($q2) => $q2->where('turmas.id', $turmaId));
            })
            ->when($filtros['situacao'] ?? null, fn ($q, $s) => $q->where('situacao', $s));

        $base = clone $query;

        // NOTA: "Com débitos" ainda assume Aluno::temDebitosPendentes() / propinas().
        // Propina foi substituído por Pagamento — falta a query real (ver observação no chat).
        $comDebitos = (clone $base)->get()->filter(fn ($a) => $a->temDebitosPendentes())->count();

        return [
            'stats' => [
                ['label' => 'Total', 'valor' => (clone $base)->count()],
                ['label' => 'Activos', 'valor' => (clone $base)->activos()->count()],
                ['label' => 'Finalistas', 'valor' => (clone $base)->finalistas()->count()],
                ['label' => 'Concluídos', 'valor' => (clone $base)->concluidos()->count()],
                ['label' => 'Com débitos', 'valor' => $comDebitos],
            ],
            'tabela' => $query->paginate($filtros['por_pagina'] ?? 15)
                ->through(fn ($aluno) => [
                    'nome' => $aluno->user?->nome,
                    'numero_processo' => $aluno->numero_processo,
                    'turma' => $aluno->turmaActual->first()?->nome,
                    'situacao' => $aluno->situacao,
                ]),
        ];
    }

    // ============================================
    // PROFESSORES
    // ============================================
    protected function dadosProfessores(array $filtros): array
    {
        $query = Professor::query()
            ->with('user')
            ->withCount(['turmas', 'classeTurnoDisciplinas as disciplinas_count'])
            ->when($filtros['pesquisa'] ?? null, function ($q, $termo) {
                 $q->whereHas('user', fn ($q2) => $q2->where('nome', 'like', "%{$termo}%")); })
            ->when($filtros['classe_id'] ?? null, function ($q, $classeId) {
                $q->whereHas('classeTurnoDisciplinas', fn ($q2) => $q2->where('classe_id', $classeId));
            });

        $base = clone $query;

        return [
            'stats' => [
                ['label' => 'Total', 'valor' => (clone $base)->count()],
                ['label' => 'Com turmas atribuídas', 'valor' => (clone $base)->has('turmas')->count()],
                ['label' => 'Sem turma', 'valor' => (clone $base)->doesntHave('turmas')->count()],
                ['label' => 'Disciplinas cobertas', 'valor' => Disciplina::whereHas('classeTurnoDisciplinas.turmaDisciplinaProfessores')->count()],
            ],
            'tabela' => $query->paginate($filtros['por_pagina'] ?? 15)
                ->through(fn ($p) => [
                    'nome' => $p->user?->nome,
                    'especialidade' => $p->especialidade,
                    'turmas' => $p->turmas_count,
                    'disciplinas' => $p->disciplinas_count,
                ]),
        ];
    }

    // ============================================
    // TURMAS
    // ============================================
    protected function dadosTurmas(array $filtros): array
    {
        $ano = $this->anoLectivo($filtros);

        $query = Turma::query()
            ->with('cursoClasseTurno.cursoClasse.classe')
            ->withCount('alunosActivos')
            ->when($ano, fn ($q) => $q->where('ano_lectivo_id', $ano->id))
            ->when($filtros['pesquisa'] ?? null, fn ($q, $t) => $q->where('nome', 'like', "%{$t}%"))
            ->when($filtros['classe_id'] ?? null, function ($q, $classeId) {
                $q->whereHas('cursoClasseTurno.cursoClasse', fn ($q2) => $q2->where('classe_id', $classeId));
            });

        $base = clone $query;
        $totais = (clone $base)->get();
        $totalVagas = $totais->sum('max_alunos');
        $totalOcupadas = $totais->sum('alunos_activos_count');

        return [
            'stats' => [
                ['label' => 'Total', 'valor' => (clone $base)->count()],
                ['label' => 'Vagas totais', 'valor' => $totalVagas],
                ['label' => 'Matriculados', 'valor' => $totalOcupadas],
                ['label' => 'Ocupação média', 'valor' => $totalVagas ? round($totalOcupadas / $totalVagas * 100).'%' : '0%'],
            ],
            'tabela' => $query->paginate($filtros['por_pagina'] ?? 15)
                ->through(fn ($t) => [
                    'nome' => $t->nome,
                    'classe' => $t->cursoClasseTurno?->cursoClasse?->classe?->nome,
                    'vagas' => $t->max_alunos,
                    'ocupacao' => $t->max_alunos ? round($t->alunos_activos_count / $t->max_alunos * 100).'%' : '0%',
                ]),
        ];
    }

    // ============================================
    // PAP — FINALISTAS
    // ============================================
    protected function dadosPap(array $filtros): array
    {
        $query = GrupoPap::query()
            ->with(['professor.user', 'turma', 'elementos.aluno.user'])
            ->when($filtros['pesquisa'] ?? null, function ($q, $termo) {
                $q->where('nome_grupo', 'like', "%{$termo}%")
                    ->orWhere('tema_grupo', 'like', "%{$termo}%");
            })
            ->when($filtros['turma_id'] ?? null, fn ($q, $id) => $q->where('turma_id', $id))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('status_aprovacao', $estado));

        $base = clone $query;

        $totalFinalistas = ElementoGrupoPap::whereHas('grupoPap', function ($q) use ($filtros) {
            $q->when($filtros['turma_id'] ?? null, fn ($q2, $id) => $q2->where('turma_id', $id));
        })->count();

        return [
            'stats' => [
                ['label' => 'Grupos', 'valor' => (clone $base)->count()],
                ['label' => 'Alunos (finalistas)', 'valor' => $totalFinalistas],
                ['label' => 'Aprovados', 'valor' => (clone $base)->aprovados()->count()],
                ['label' => 'Pendentes', 'valor' => (clone $base)->pendentes()->count()],
                ['label' => 'Reprovados', 'valor' => (clone $base)->reprovados()->count()],
            ],
            'tabela' => $query->paginate($filtros['por_pagina'] ?? 15)
                ->through(fn ($g) => [
                    'grupo' => $g->nome_grupo,
                    'tema' => $g->tema_grupo,
                    'orientador' => $g->professor?->user?->name,
                    'turma' => $g->turma?->nome,
                    'estado' => $g->status_aprovacao,
                    'nota' => $g->nota_final,
                    'data_defesa' => $g->data_defesa?->format('d/m/Y'),
                ]),
        ];
    }

    // ============================================
    // FINANCEIRO
    // ============================================
    protected function dadosFinanceiro(array $filtros): array
    {
        $inicio = $filtros['data_inicio'] ?? now()->startOfMonth()->toDateString();
        $fim = $filtros['data_fim'] ?? now()->toDateString();

        $query = Pagamento::query()
            ->with('aluno.user')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->when($filtros['pesquisa'] ?? null, function ($q, $termo) {
                $q->whereHas('aluno.user', fn ($q2) => $q2->where('nome', 'like', "%{$termo}%"));
            });

        $base = clone $query;
        $arrecadado = (clone $base)->sum('valor_total');

        return [
            'stats' => [
                ['label' => 'Arrecadado no período', 'valor' => number_format($arrecadado, 2, ',', '.').' Kz'],
                ['label' => 'Pagamentos registados', 'valor' => (clone $base)->count()],
                // "Em dívida" e "Taxa de cobrança" dependem de ItemPagavel + PagamentoPeriodo —
                // falta a lógica (ver observação no chat).
            ],
            'tabela' => $query->orderByDesc('data_pagamento')->paginate($filtros['por_pagina'] ?? 15)
                ->through(fn ($p) => [
                    'aluno' => $p->aluno?->user?->nome,
                    'valor' => number_format($p->valor_total, 2, ',', '.').' Kz',
                    'data' => $p->data_pagamento->format('d/m/Y'),
                    'numero_recibo' => $p->numero_recibo,
                ]),
        ];
    }
}