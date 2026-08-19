<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagamento\StorePagamentoRequest;
use App\Http\Requests\Pagamento\UpdatePagamentoRequest;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\PagamentoItem;
use App\Models\Tenant\Turma;
use App\Services\PropinaNotificacaoService;
use App\Services\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PagamentoController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador,
        private readonly PropinaNotificacaoService $notificador,
    ) {}

    public function index(Request $request)
    {
        $instituicaoId = $request->user()->instituicao_id;

        Log::info('PagamentoController@index - início', [
            'user_id' => $request->user()->id,
            'instituicao_id' => $instituicaoId,
        ]);

        $pagamentos = Pagamento::query()
            ->where('instituicao_id', $instituicaoId)
            ->with(['aluno.user:id,nome,instituicao_id'])
            ->orderByDesc('data_pagamento')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Pagamento $p) use ($request) {
                $classes = $p->itens()
                    ->with('itemPagavel.cursoClasse.classe')
                    ->get()
                    ->pluck('itemPagavel.cursoClasse.classe.nome')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $podeApagar = $request->user()->can('delete', $p);

                Log::debug('PagamentoController@index - verificação can.delete', [
                    'pagamento_id' => $p->id,
                    'user_id' => $request->user()->id,
                    'user_tem_permissao_pagamentos_delete' => $request->user()->can('pagamentos.delete'),
                    'user_instituicao_id' => $request->user()->instituicao_id,
                    'pagamento_aluno_instituicao_id' => $p->aluno?->user?->instituicao_id,
                    'instituicoes_batem' => $p->aluno?->user?->instituicao_id === $request->user()->instituicao_id,
                    'resultado_policy_delete' => $podeApagar,
                ]);

                return [
                    'id' => $p->id,
                    'aluno' => $p->aluno->user->nome,
                    'valor_total' => $p->valor_total,
                    'metodo' => $p->metodo,
                    'referencia' => $p->referencia,
                    'data_pagamento' => $p->data_pagamento->format('d/m/Y'),
                    'classes' => $classes ?: 'Geral',
                    'can' => [
                        'delete' => $podeApagar ?? true,
                    ],
                ];
            });

        Log::info('PagamentoController@index - total de pagamentos', ['total' => $pagamentos->total()]);

        $turmas = Turma::query()
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($q) use ($instituicaoId) {
                $q->where('instituicao_id', $instituicaoId);
            })
            ->with(['cursoClasseTurno.cursoClasse.classe'])
            ->get()
            ->map(fn (Turma $t) => [
                'id' => $t->id,
                'nome' => $t->nome.' — '.($t->cursoClasseTurno?->cursoClasse?->classe?->nome ?? ''),
            ]);

        $statusFiltro = $request->input('status_propina'); // 'pagos' | 'nao_pagos' | 'pendentes'

        $alunosPorStatus = null;

        if ($statusFiltro) {
            $alunosPorStatus = $this->alunosAgrupadosPorStatus($request, $statusFiltro);
        }

        return Inertia::render('pagamentos/index', [
            'pagamentos' => $pagamentos,
            'turmas' => $turmas,
            'can' => [
                'create' => Auth::user()->can('create', Pagamento::class),
            ],
            'filtros' => $request->only(['aluno_id', 'data_inicio', 'data_fim']),
            'statusFiltro' => $statusFiltro,
            'alunosPorStatus' => $alunosPorStatus,
        ]);
    }

    /**
     * Retorna alunos filtrados por status de propina (pagos/nao_pagos/pendentes),
     * agrupados por Classe -> Turma, considerando o ano lectivo activo.
     */
    private function alunosAgrupadosPorStatus(Request $request, string $status): array
    {
        $anoLectivoId = AnoLectivo::activo()?->id;

        $alunos = Aluno::whereIn('situacao', ['activo', 'finalista', 'reprovado'])
            ->doAnoLectivo($anoLectivoId)
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $request->user()->instituicao_id))
            ->with([
                // FIX: 'instituicao_id' precisa de ser seleccionado aqui.
                // Sem ele, $aluno->user->instituicao_id vinha null dentro do
                // VerificadorPropinaService, e a query de ItemPagavel
                // (where('instituicao_id', null)) nunca encontrava itens,
                // fazendo todos os alunos caírem como "em dia" por omissão.
                'user:id,nome,instituicao_id',
                'inscricao.candidato:id,nome',
                'inscricao.cursoClasseTurno.turno:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turmas' => fn ($q) => $q->wherePivot('activo', true)
                    ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
            ])
            ->get()
            ->filter() // remove qualquer elemento nulo da colecção antes de continuar
            ->values();

        Log::debug('PagamentoController@alunosAgrupadosPorStatus - alunos carregados', [
            'status' => $status,
            'total_alunos' => $alunos->count(),
        ]);

        $filtrados = $alunos->filter(function (Aluno $aluno) use ($status) {
            $pendencias = $this->verificador->pendenciasDoAluno($aluno);
            $emDia = empty($pendencias);
            $mesesEmAtraso = count($pendencias);

            return match ($status) {
                'pagos' => $emDia,
                'nao_pagos' => ! $emDia,
                // "pendentes" = em atraso de 1 ou mais meses (mesma condição de nao_pagos,
                // mantido separado caso queiras diferenciar limiares no futuro)
                'pendentes' => ! $emDia && $mesesEmAtraso >= 1,
                default => true,
            };
        });

        Log::debug('PagamentoController@alunosAgrupadosPorStatus - alunos filtrados', [
            'status' => $status,
            'total_filtrados' => $filtrados->count(),
        ]);

        // Agrupar por Classe -> Turma, mantendo nome, curso, turno
        $agrupado = $filtrados
            ->groupBy(fn (Aluno $aluno) => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome ?? 'Sem classe')
            ->map(function ($alunosDaClasse) {
                return $alunosDaClasse
                    ->groupBy(fn (Aluno $aluno) => $aluno->turmas->first()?->nome ?? 'Sem turma')
                    ->map(function ($alunosDaTurma) {
                        return $alunosDaTurma->map(fn (Aluno $aluno) => [
                            'id' => $aluno->id,
                            'nome' => $aluno->inscricao?->candidato?->nome ?? $aluno->user?->nome,
                            'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                            'turma' => $aluno->turmas->first()?->nome,
                            'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome,
                            'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
                        ])->values();
                    });
            });

        return $agrupado->toArray();
    }

    public function create(Request $request)
    {
        $instituicaoId = $request->user()->instituicao_id;

        Log::info('PagamentoController@create - início', [
            'instituicao_id' => $instituicaoId,
            'aluno_id' => $request->query('aluno_id'),
            'query_params' => $request->query(),
        ]);

        // Buscar aluno e turma
        $aluno = null;
        $turma = null;
        $cursoClasseId = null;
        $classeId = null;

        if ($request->filled('aluno_id')) {
            $aluno = Aluno::with('turmaActual')->find($request->aluno_id);
            if ($aluno) {
                $turma = $aluno->turmaActual->first();
                if ($turma) {
                    $turma->loadMissing(['cursoClasseTurno.cursoClasse']);
                    $cursoClasseId = $turma->curso_classe_id
                    ?? $turma->cursoClasseTurno->curso_classe_id
                    ?? null;
                    $classeId = $turma->classe_id
                    ?? $turma->cursoClasseTurno->cursoClasse->classe_id
                    ?? null;

                    Log::debug('PagamentoController@create - turma do aluno', [
                        'aluno_id' => $aluno->id,
                        'turma_id' => $turma->id,
                        'curso_classe_id' => $cursoClasseId,
                        'classe_id' => $classeId,
                    ]);
                } else {
                    Log::debug('PagamentoController@create - aluno sem turma', ['aluno_id' => $aluno->id]);
                }
            } else {
                Log::warning('PagamentoController@create - aluno não encontrado', ['aluno_id' => $request->aluno_id]);
            }
        }

        // Query de itens ativos
        $itensQuery = ItemPagavel::query()
            ->where('instituicao_id', $instituicaoId)
            ->ativos();

        if ($turma && ($cursoClasseId || $classeId)) {
            $itensQuery->where(function ($q) use ($cursoClasseId, $classeId) {
                // 1. Itens universais
                $q->whereNull('curso_classe_id');

                // 2. Diretamente vinculados ao curso_classe da turma
                if ($cursoClasseId) {
                    $q->orWhere('curso_classe_id', $cursoClasseId);
                }

                // 3. Vinculados a um curso_classe com a mesma classe_id da turma
                if ($classeId) {
                    $q->orWhereExists(function ($sub) use ($classeId) {
                        $sub->from('curso_classe')
                            ->whereColumn('curso_classe.id', 'itens_pagaveis.curso_classe_id')
                            ->where('curso_classe.classe_id', $classeId);
                    });
                }
            });

            Log::debug('PagamentoController@create - filtro aplicado (com vínculo)', [
                'curso_classe_id' => $cursoClasseId,
                'classe_id' => $classeId,
            ]);
        } else {
            $itensQuery->whereNull('curso_classe_id');
            Log::debug('PagamentoController@create - sem vínculo – a mostrar apenas itens universais');
        }

        $itensPagaveis = $itensQuery->get(['id', 'nome', 'valor', 'frequencia', 'curso_classe_id', 'multa_dias_tolerancia', 'multa_valor']);

        $itensPagaveis->each(function ($item) {
            $item->load('cursoClasse.classe');
            Log::debug('PagamentoController@create - ITEM RETORNADO', [
                'item_id' => $item->id,
                'item_nome' => $item->nome,
                'curso_classe_id' => $item->curso_classe_id,
                'classe_associada' => $item->cursoClasse?->classe?->nome ?? 'Nenhuma',
                'curso_associado' => $item->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome ?? 'Nenhum',
                'frequencia' => $item->frequencia,
                'valor' => $item->valor,
                'multa_dias_tolerancia' => $item->multa_dias_tolerancia,
                'multa_valor' => $item->multa_valor,
            ]);
        });

        Log::info('PagamentoController@create - total de itens retornados', [
            'total' => $itensPagaveis->count(),
            'ids' => $itensPagaveis->pluck('id')->toArray(),
        ]);

        $alunos = Aluno::query()
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with('user:id,nome')
            ->activos()
            ->get(['id', 'user_id'])
            ->map(fn (Aluno $a) => [
                'id' => $a->id,
                'nome' => $a->user->nome,
            ]);

        $paidRecord = [];
        $pendenciasComMulta = [];

        if ($request->filled('aluno_id') && $aluno) {
            $paidRecord = $this->paidRecordDoAluno($request->aluno_id);
            Log::debug('PagamentoController@create - paidRecord', ['paidRecord' => $paidRecord]);

            // Pendências reais do aluno (com valor base + multa já calculados),
            // para o frontend saber exactamente quanto cobrar por cada mês em
            // atraso, sem ter de recalcular a multa no lado do cliente.
            $pendencias = $this->verificador->pendenciasDoAluno($aluno);

            $pendenciasComMulta = collect($pendencias)
                ->filter(fn ($p) => $p['mes'] !== null) // só mensais têm multa
                ->groupBy('item_pagavel_id')
                ->map(fn ($porItem) => $porItem->map(fn ($p) => [
                    'mes' => $p['mes'],
                    'ano' => $p['ano'],
                    'valor_base' => $p['valor_base'],
                    'multa' => $p['multa'],
                    'valor' => $p['valor'],
                ])->values())
                ->toArray();

            Log::debug('PagamentoController@create - pendências com multa calculadas', [
                'aluno_id' => $aluno->id,
                'pendenciasComMulta' => $pendenciasComMulta,
            ]);
        }

        return Inertia::render('pagamentos/create', [
            'alunos' => $alunos,
            'itensPagaveis' => $itensPagaveis,
            'paidRecord' => $paidRecord,
            'pendenciasComMulta' => $pendenciasComMulta,
        ]);
    }

    private function paidRecordDoAluno(string $alunoId): array
    {
        return PagamentoItem::query()
            ->where('aluno_id', $alunoId)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id')
            ->map(fn ($linhas) => $linhas
                ->pluck('mes')
                ->filter(fn ($mes) => $mes !== null)
                ->map(fn ($mes) => (int) $mes)
                ->values()
                ->unique()
                ->values()
                ->all())
            ->toArray();
    }

    public function store(StorePagamentoRequest $request)
    {
        Log::info('PagamentoController@store - início', [
            'aluno_id' => $request->input('aluno_id'),
            'metodo' => $request->input('metodo'),
            'itens_count' => count($request->input('itens', [])),
        ]);

        DB::transaction(function () use ($request) {
            $valorTotal = 0;
            $linhasParaCriar = [];

            foreach ($request->input('itens') as $linha) {
                $item = ItemPagavel::findOrFail($linha['item_pagavel_id']);
                $meses = $item->frequencia === 'mensal' ? $linha['meses'] : [0];
                $temValorManual = array_key_exists('valor', $linha) && $linha['valor'] !== null && $linha['valor'] !== '';

                foreach ($meses as $mes) {
                    if ($temValorManual) {
                        // Utilizador sobrepôs o valor manualmente (ex: desconto,
                        // acordo especial) — respeita-se tal como já acontecia.
                        $valorUnitario = (float) $linha['valor'];

                        Log::debug('PagamentoController@store - valor manual aplicado (multa não recalculada)', [
                            'item_id' => $item->id,
                            'mes' => $mes,
                            'ano' => $linha['ano'],
                            'valor_manual' => $valorUnitario,
                        ]);
                    } elseif ($item->frequencia === 'mensal') {
                        // Calcula o valor correto incluindo multa por atraso,
                        // usando a mesma lógica que gerou a pendência mostrada
                        // ao utilizador — evita cobrar só a propina "seca".
                        $valores = $this->verificador->valorComMulta($item, $mes, $linha['ano']);
                        $valorUnitario = $valores['valor'];

                        Log::debug('PagamentoController@store - valor calculado com multa', [
                            'item_id' => $item->id,
                            'mes' => $mes,
                            'ano' => $linha['ano'],
                            'valor_base' => $valores['valor_base'],
                            'multa' => $valores['multa'],
                            'valor_total_mes' => $valorUnitario,
                        ]);
                    } else {
                        $valorUnitario = (float) $item->valor;
                    }

                    $linhasParaCriar[] = [
                        'item_pagavel_id' => $item->id,
                        'aluno_id' => $request->input('aluno_id'),
                        'mes' => $mes,
                        'ano' => $linha['ano'],
                        'valor' => $valorUnitario,
                    ];
                    $valorTotal += $valorUnitario;
                }

                Log::debug('PagamentoController@store - item processado', [
                    'item_id' => $item->id,
                    'item_nome' => $item->nome,
                    'frequencia' => $item->frequencia,
                    'meses' => $meses,
                    'tem_valor_manual' => $temValorManual,
                ]);
            }

            $pagamento = Pagamento::create([
                'aluno_id' => $request->input('aluno_id'),
                'instituicao_id' => $request->user()->instituicao_id,
                'registado_por' => $request->user()->id,
                'data_pagamento' => $request->input('data_pagamento'),
                'valor_total' => $valorTotal,
                'metodo' => $request->input('metodo'),
                'referencia' => $request->input('referencia'),
                'observacoes' => $request->input('observacoes'),
            ]);

            $pagamento->itens()->createMany($linhasParaCriar);

            Log::info('PagamentoController@store - pagamento registado', [
                'pagamento_id' => $pagamento->id,
                'valor_total' => $valorTotal,
                'itens_quantidade' => count($linhasParaCriar),
            ]);

            $this->resolverNotificacoesSePropinaEmDia($request->input('aluno_id'));
        });

        return redirect()->route('pagamentos.index')->with('success', 'Pagamento registado com sucesso.');
    }

    /**
     * Depois de registar um pagamento, verifica se o aluno ficou em dia
     * com as propinas. Se sim, marca como lidas as notificações de
     * "propina em atraso" que ainda estavam por ler. Se ainda houver
     * dívida (ex: pagou só parte dos meses), a notificação mantém-se.
     */
    private function resolverNotificacoesSePropinaEmDia(string $alunoId): void
    {
        Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - início', [
            'aluno_id' => $alunoId,
        ]);

        $aluno = Aluno::with('user')->find($alunoId);

        if (! $aluno || ! $aluno->user) {
            Log::debug('PagamentoController@resolverNotificacoesSePropinaEmDia - aluno ou user não encontrado', [
                'aluno_id' => $alunoId,
            ]);

            return;
        }

        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        $this->notificador->resolverSePropinaEmDia($aluno->user, $pendencias);
    }

    public function show(Pagamento $pagamento)
    {
        Log::info('PagamentoController@show - início', ['pagamento_id' => $pagamento->id]);

        $pagamento->load(['aluno.user:id,nome', 'registadoPor:id,nome']);

        $itens = $pagamento->itens()
            ->with('itemPagavel:id,nome,frequencia,valor')
            ->orderBy('ano')
            ->orderBy('mes')
            ->paginate(10)
            ->through(function ($item) {
                $valorBase = (float) ($item->itemPagavel->valor ?? $item->valor);
                $valorPago = (float) $item->valor;
                $multa = max(0, round($valorPago - $valorBase, 2));

                Log::debug('PagamentoController@show - item com cálculo de multa', [
                    'item_id' => $item->id,
                    'item_pagavel_id' => $item->item_pagavel_id,
                    'valor_pago' => $valorPago,
                    'valor_base_atual_do_catalogo' => $valorBase,
                    'multa_calculada' => $multa,
                ]);

                return [
                    'id' => $item->id,
                    'nome' => $item->itemPagavel->nome,
                    'frequencia' => $item->itemPagavel->frequencia,
                    'mes' => $item->mes,
                    'ano' => $item->ano,
                    'valor' => $item->valor,
                    'valor_base' => $valorBase,
                    'multa' => $multa,
                ];
            })
            ->withQueryString();

        Log::debug('PagamentoController@show - itens retornados', ['total' => $itens->total()]);

        return Inertia::render('pagamentos/show', [
            'pagamento' => [
                'id' => $pagamento->id,
                'aluno' => $pagamento->aluno->user->nome,
                'registado_por' => $pagamento->registadoPor->nome,
                'data_pagamento' => $pagamento->data_pagamento->format('d/m/Y'),
                'valor_total' => $pagamento->valor_total,
                'metodo' => $pagamento->metodo,
                'referencia' => $pagamento->referencia,
                'observacoes' => $pagamento->observacoes,
                'itens' => $itens,
            ],
        ]);
    }

    public function edit(Pagamento $pagamento)
    {
        Log::info('PagamentoController@edit - início', ['pagamento_id' => $pagamento->id]);

        return Inertia::render('pagamentos/edit', [
            'pagamento' => $pagamento->load('itens.itemPagavel', 'aluno'),
        ]);
    }

    public function update(UpdatePagamentoRequest $request, Pagamento $pagamento)
    {
        Log::info('PagamentoController@update - início', [
            'pagamento_id' => $pagamento->id,
            'dados' => $request->validated(),
        ]);

        $pagamento->update($request->validated());

        Log::info('PagamentoController@update - pagamento atualizado', ['pagamento_id' => $pagamento->id]);

        return back()->with('success', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Pagamento $pagamento)
    {
        // $this->authorize('delete', $pagamento);

        Log::warning('PagamentoController@destroy - anulando pagamento', [
            'pagamento_id' => $pagamento->id,
            'aluno_id' => $pagamento->aluno_id,
            'valor_total' => $pagamento->valor_total,
        ]);

        $alunoId = $pagamento->aluno_id;

        DB::transaction(function () use ($pagamento) {
            $pagamento->itens()->delete();
            $pagamento->delete();
        });

        Log::info('PagamentoController@destroy - pagamento anulado', ['pagamento_id' => $pagamento->id]);

        // Depois de anular, a dívida que este pagamento tinha "resolvido"
        // pode voltar a existir. Se voltar, é preciso reactivar o aviso
        // para o aluno — caso contrário ele fica em atraso sem qualquer
        // notificação, já que a anterior foi marcada como lida no momento
        // do pagamento.
        $this->notificarSePropinaVoltouEmAtraso($alunoId);

        return back()->with('success', 'Pagamento anulado com sucesso.');
    }

    /**
     * Depois de anular um pagamento, verifica se o aluno voltou a ter
     * pendências de propina. Se sim, cria uma notificação nova (com
     * assinatura própria do estado actual da dívida), para o aluno não
     * ficar em atraso sem qualquer aviso.
     */
    private function notificarSePropinaVoltouEmAtraso(string $alunoId): void
    {
        Log::debug('PagamentoController@notificarSePropinaVoltouEmAtraso - início', [
            'aluno_id' => $alunoId,
        ]);

        $aluno = Aluno::with('user')->find($alunoId);

        if (! $aluno || ! $aluno->user) {
            Log::debug('PagamentoController@notificarSePropinaVoltouEmAtraso - aluno ou user não encontrado', [
                'aluno_id' => $alunoId,
            ]);

            return;
        }

        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        $this->notificador->notificarSeEmAtraso($aluno->user, $pendencias);
    }
}
