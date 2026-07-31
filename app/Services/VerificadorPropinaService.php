<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\PagamentoItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerificadorPropinaService
{
    private const TERMOS_BLOQUEIO = ['propina', 'propinas'];

    /**
     * Retorna a lista de pendências do aluno (somente itens de bloqueio).
     * Array vazio = aluno em dia.
     *
     * @return array<int, array{item_pagavel_id: string, nome: string, frequencia: string, mes: int|null, ano: int}>
     */
    public function pendenciasDoAluno(Aluno $aluno): array
    {
        Log::debug('[VerificadorPropinaService] INICIO pendenciasDoAluno', [
            'aluno_id' => $aluno->id,
            'aluno_nome' => $aluno->nome ?? 'N/A',
        ]);

        $turma = $aluno->turmaActual()->first();
        if (! $turma) {
            Log::debug('[VerificadorPropinaService] SEM TURMA ATUAL — retorna vazio', [
                'aluno_id' => $aluno->id,
            ]);
            return [];
        }

        $anoLectivo = $turma->anoLectivo;
        if (! $anoLectivo) {
            Log::debug('[VerificadorPropinaService] SEM ANO LECTIVO — retorna vazio', [
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
            ]);
            return [];
        }

        // --------------------------------------------------------------
        // CORREÇÃO: obter curso_classe_id e classe_id via relacionamento
        // --------------------------------------------------------------
        $turma->loadMissing(['cursoClasseTurno.cursoClasse']);

        // Prioriza os campos diretos (caso existam), senão busca via relação
        $cursoClasseId = $turma->curso_classe_id
                        ?? $turma->cursoClasseTurno->curso_classe_id
                        ?? null;

        $classeId = $turma->classe_id
                    ?? $turma->cursoClasseTurno->cursoClasse->classe_id
                    ?? null;

        Log::debug('[VerificadorPropinaService] DADOS TURMA E ANO', [
            'aluno_id'           => $aluno->id,
            'turma_id'           => $turma->id,
            'turma_nome'         => $turma->nome ?? 'N/A',
            'curso_classe_id'    => $cursoClasseId,
            'classe_id'          => $classeId,
            'ano_lectivo_id'     => $anoLectivo->id,
            'ano_lectivo_inicio' => (string) $anoLectivo->data_inicio,
            'ano_lectivo_fim'    => (string) $anoLectivo->data_fim,
        ]);

        // --- Período de cobrança ---
        $dataMatricula = $aluno->data_matricula ?? $aluno->created_at;
        $inicioAno = Carbon::parse($anoLectivo->data_inicio)->startOfMonth();
        $inicio = $dataMatricula ? Carbon::parse($dataMatricula)->startOfMonth() : $inicioAno;
        if ($inicio->lt($inicioAno)) {
            $inicio = $inicioAno;
        }

        $fim = Carbon::now()->startOfMonth();
        $fimAno = Carbon::parse($anoLectivo->data_fim)->startOfMonth();
        if ($fim->gt($fimAno)) {
            $fim = $fimAno;
        }

        Log::debug('[VerificadorPropinaService] PERIODO COBRANCA CALCULADO', [
            'aluno_id'       => $aluno->id,
            'data_matricula' => (string) $dataMatricula,
            'inicio_ano'     => (string) $inicioAno,
            'fim_ano'        => (string) $fimAno,
            'inicio_periodo' => (string) $inicio,
            'fim_periodo'    => (string) $fim,
            'meses_entre'    => $inicio->diffInMonths($fim) + 1,
        ]);

        // --- Query base ---
        $query = ItemPagavel::query()
            ->where('instituicao_id', $aluno->user->instituicao_id)
            ->ativos();

        Log::debug('[VerificadorPropinaService] CONSTRUINDO QUERY', [
            'aluno_id'              => $aluno->id,
            'tem_curso_classe_id'   => ! is_null($cursoClasseId),
            'tem_classe_id'         => ! is_null($classeId),
            'curso_classe_id_valor' => $cursoClasseId,
            'classe_id_valor'       => $classeId,
        ]);

        // --------------------------------------------------------------
        // CORREÇÃO: usa os IDs obtidos via relacionamento
        // --------------------------------------------------------------
        if ($cursoClasseId || $classeId) {
            $query->where(function ($q) use ($cursoClasseId, $classeId) {
                // 1. Itens universais (sem vínculo com curso_classe)
                $q->whereNull('curso_classe_id');

                // 2. Diretamente vinculados ao curso_classe da turma
                if ($cursoClasseId) {
                    $q->orWhere('curso_classe_id', $cursoClasseId);
                }

                // 3. Vinculados a um curso_classe cuja classe_id seja igual à da turma
                if ($classeId) {
                    $q->orWhereExists(function ($sub) use ($classeId) {
                        $sub->from('curso_classe')
                            ->whereColumn('curso_classe.id', 'itens_pagaveis.curso_classe_id')
                            ->where('curso_classe.classe_id', $classeId);
                    });
                }
            });
            $modo = 'associacao';
        } else {
            // Fallback: turma sem vínculo → apenas itens universais
            $query->whereNull('curso_classe_id');
            $modo = 'fallback_globais';
            Log::debug('[VerificadorPropinaService] FALLBACK ATIVADO (turma sem vínculo)', [
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
            ]);
        }

        $todosItens = $query->get();
        Log::debug('[VerificadorPropinaService] ITENS ENCONTRADOS (brutos)', [
            'aluno_id' => $aluno->id,
            'modo'     => $modo,
            'total'    => $todosItens->count(),
            'itens'    => $todosItens->map(fn ($i) => [
                'id'              => $i->id,
                'nome'            => $i->nome,
                'frequencia'      => $i->frequencia,
                'curso_classe_id' => $i->curso_classe_id,
            ])->toArray(),
        ]);

        // --- Filtra apenas os itens de bloqueio (baseado no nome) ---
        $itensAplicaveis = $todosItens->filter(function ($item) {
            $isBloqueio = $this->ehItemDeBloqueio($item);
            Log::debug('[VerificadorPropinaService] VERIFICANDO ITEM PARA BLOQUEIO', [
                'item_id'      => $item->id,
                'item_nome'    => $item->nome,
                'contem_termo' => $isBloqueio,
                'termos_busca' => self::TERMOS_BLOQUEIO,
            ]);
            return $isBloqueio;
        });

        Log::debug('[VerificadorPropinaService] ITENS APÓS FILTRO BLOQUEIO', [
            'aluno_id'       => $aluno->id,
            'total_bloqueio' => $itensAplicaveis->count(),
            'itens_bloqueio' => $itensAplicaveis->map(fn ($i) => [
                'id'         => $i->id,
                'nome'       => $i->nome,
                'frequencia' => $i->frequencia,
            ])->toArray(),
        ]);

        if ($itensAplicaveis->isEmpty()) {
            Log::debug('[VerificadorPropinaService] NENHUM ITEM DE BLOQUEIO — retorna vazio', [
                'aluno_id' => $aluno->id,
            ]);
            return [];
        }

        // --- Pagamentos existentes do aluno ---
        $pagamentosExistentes = PagamentoItem::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id');

        Log::debug('[VerificadorPropinaService] PAGAMENTOS REGISTADOS', [
            'aluno_id'       => $aluno->id,
            'total_registos' => $pagamentosExistentes->flatten()->count(),
            'por_item'       => $pagamentosExistentes->map(function ($items, $itemId) {
                return [
                    'item_pagavel_id' => $itemId,
                    'total'           => $items->count(),
                    'meses_anos'      => $items->map(fn ($p) => $p->mes . '/' . $p->ano)->toArray(),
                ];
            })->values()->toArray(),
        ]);

        // --- Cálculo das pendências ---
        $pendencias = collect();

        foreach ($itensAplicaveis as $item) {
            Log::debug('[VerificadorPropinaService] PROCESSANDO ITEM', [
                'item_id'    => $item->id,
                'item_nome'  => $item->nome,
                'frequencia' => $item->frequencia,
            ]);

            $pagosDoItem = $pagamentosExistentes->get($item->id, collect());

            if ($item->frequencia === 'mensal') {
                Log::debug('[VerificadorPropinaService] ITEM MENSAL - chamando pendenciasMensais', [
                    'item_id'               => $item->id,
                    'periodo_inicio'        => (string) $inicio,
                    'periodo_fim'           => (string) $fim,
                    'pagamentos_existentes' => $pagosDoItem->map(fn ($p) => $p->mes . '/' . $p->ano)->toArray(),
                ]);
                $pendenciasDoItem = $this->pendenciasMensais($item, $pagosDoItem, $inicio, $fim);
                $pendencias = $pendencias->merge($pendenciasDoItem);
            } else {
                $anoCorrente = $anoLectivo->data_inicio->year;
                $jaPago = $pagosDoItem->where('ano', $anoCorrente)->isNotEmpty();

                Log::debug('[VerificadorPropinaService] ITEM ANUAL/UNICO', [
                    'item_id'        => $item->id,
                    'nome'           => $item->nome,
                    'frequencia'     => $item->frequencia,
                    'ano_corrente'   => $anoCorrente,
                    'ja_pago'        => $jaPago,
                    'pagamentos_ano' => $pagosDoItem->where('ano', $anoCorrente)->map(fn ($p) => $p->mes . '/' . $p->ano)->toArray(),
                ]);

                if (! $jaPago) {
                    $pendencias->push([
                        'item_pagavel_id' => $item->id,
                        'nome'            => $item->nome,
                        'frequencia'      => $item->frequencia,
                        'mes'             => null,
                        'ano'             => $anoCorrente,
                    ]);
                }
            }
        }

        $resultado = $pendencias->values()->all();

        Log::debug('[VerificadorPropinaService] RESULTADO FINAL', [
            'aluno_id'         => $aluno->id,
            'total_pendencias' => count($resultado),
            'pendencias'       => $resultado,
        ]);

        return $resultado;
    }

    /**
     * Verifica se o aluno está em dia (sem pendências).
     */
    public function estaEmDia(Aluno $aluno): bool
    {
        return empty($this->pendenciasDoAluno($aluno));
    }

    private function ehItemDeBloqueio(ItemPagavel $item): bool
    {
        $nome = Str::lower(Str::ascii($item->nome));
        foreach (self::TERMOS_BLOQUEIO as $termo) {
            if (Str::contains($nome, $termo)) {
                return true;
            }
        }
        return false;
    }

    private function pendenciasMensais(ItemPagavel $item, Collection $pagos, Carbon $inicio, Carbon $fim): Collection
    {
        $pendencias = collect();
        $cursor = $inicio->copy();
        $mesesProcessados = [];

        while ($cursor->lte($fim)) {
            $mes = $cursor->month;
            $ano = $cursor->year;
            $chave = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);

            $pago = $pagos->contains(fn ($p) =>
                (int) $p->mes === $mes && (int) $p->ano === $ano
            );

            $mesesProcessados[] = [
                'mes_ano' => $chave,
                'pago'    => $pago,
            ];

            if (! $pago) {
                $pendencias->push([
                    'item_pagavel_id' => $item->id,
                    'nome'            => $item->nome,
                    'frequencia'      => $item->frequencia,
                    'mes'             => $mes,
                    'ano'             => $ano,
                ]);
            }

            $cursor->addMonth();
        }

        Log::debug('[VerificadorPropinaService] pendenciasMensais - DETALHES', [
            'item_id'                  => $item->id,
            'item_nome'                => $item->nome,
            'meses_analisados'         => $mesesProcessados,
            'total_pendencias_geradas' => $pendencias->count(),
        ]);

        return $pendencias;
    }

    public function statusAlunos(Collection $alunos): Collection
    {
        return $alunos->map(function (Aluno $aluno) {
            $pendencias = $this->pendenciasDoAluno($aluno);

            return [
                'aluno_id'   => $aluno->id,
                'nome'       => $aluno->inscricao?->candidato?->nome,
                'em_dia'     => empty($pendencias),
                'pendencias' => $pendencias,
            ];
        });
    }
}