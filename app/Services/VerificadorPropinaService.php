<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\PagamentoItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VerificadorPropinaService
{
    public function pendenciasDoAluno(Aluno $aluno): array
    {
        $turma = $aluno->turmaActual()->first();

        if (! $turma) {
            Log::debug('[VerificadorPropinaService] aluno sem turma actual — não barra', [
                'aluno_id' => $aluno->id,
            ]);
            return [];
        }

        $anoLectivo = $turma->anoLectivo;

        if (! $anoLectivo) {
            Log::debug('[VerificadorPropinaService] ano lectivo não encontrado — não barra', [
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'ano_lectivo_id' => $turma->ano_lectivo_id,
            ]);
            return [];
        }

        // Data de início do período de cobrança: máximo entre início do ano lectivo e data de matrícula
        $dataMatricula = $aluno->data_matricula ?? $aluno->created_at; // ajuste conforme seu modelo
        $inicioAno = Carbon::parse($anoLectivo->data_inicio)->startOfMonth();
        $inicio = $dataMatricula ? Carbon::parse($dataMatricula)->startOfMonth() : $inicioAno;
        // Garantir que não comece antes do início do ano lectivo
        if ($inicio->lt($inicioAno)) {
            $inicio = $inicioAno;
        }

        // Fim: mês atual (limitado ao fim do ano lectivo)
        $fim = Carbon::now()->startOfMonth();
        $fimAno = Carbon::parse($anoLectivo->data_fim)->startOfMonth();
        if ($fim->gt($fimAno)) {
            $fim = $fimAno;
        }

        Log::debug('[VerificadorPropinaService] contexto do aluno', [
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'curso_classe_id' => $turma->curso_classe_id,
            'ano_lectivo_id' => $anoLectivo->id,
            'ano_lectivo_nome' => $anoLectivo->data_inicio,
            'ano_lectivo_data_inicio' => (string) $anoLectivo->data_inicio,
            'ano_lectivo_data_fim' => (string) $anoLectivo->data_fim,
            'data_matricula' => (string) $dataMatricula,
            'periodo_cobranca_inicio' => (string) $inicio,
            'periodo_cobranca_fim' => (string) $fim,
        ]);

        $itensAplicaveis = ItemPagavel::query()
            ->where('instituicao_id', $aluno->user->instituicao_id)
            ->ativos()
            ->where(function ($q) use ($turma) {
                $q->whereNull('curso_classe_id')
                    ->orWhere('curso_classe_id', $turma->curso_classe_id);
            })
            ->get();

        Log::debug('[VerificadorPropinaService] itens pagáveis aplicáveis', [
            'aluno_id' => $aluno->id,
            'total' => $itensAplicaveis->count(),
            'itens' => $itensAplicaveis->pluck('nome', 'id')->toArray(),
        ]);

        $pagamentosExistentes = PagamentoItem::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id');

        Log::debug('[VerificadorPropinaService] pagamentos existentes do aluno', [
            'aluno_id' => $aluno->id,
            'total_registos' => $pagamentosExistentes->flatten()->count(),
            'por_item' => $pagamentosExistentes->map->count()->toArray(),
        ]);

        $pendencias = collect();
        $totalPagos = 0;
        $pagosList = [];

        foreach ($itensAplicaveis as $item) {
            $pagosDoItem = $pagamentosExistentes->get($item->id, collect());

            if ($item->frequencia === 'mensal') {
                [$pendenciasDoItem, $pagosDoItemDetalhados] = $this->pendenciasMensais(
                    $item,
                    $pagosDoItem,
                    $inicio,
                    $fim
                );

                Log::debug('[VerificadorPropinaService] item mensal avaliado', [
                    'item_id' => $item->id,
                    'item_nome' => $item->nome,
                    'meses_pagos' => $pagosDoItemDetalhados->pluck('mes_ano')->toArray(),
                    'meses_em_falta' => $pendenciasDoItem->map(fn($p) => "{$p['mes']}/{$p['ano']}")->toArray(),
                ]);

                $pendencias = $pendencias->merge($pendenciasDoItem);
                $totalPagos += $pagosDoItemDetalhados->count();
                $pagosList = array_merge($pagosList, $pagosDoItemDetalhados->toArray());
            } else {
                // única ou anual
                $anoCorrente = $anoLectivo->data_inicio->year;
                $jaPago = $pagosDoItem->where('ano', $anoCorrente)->isNotEmpty();

                Log::debug('[VerificadorPropinaService] item único/anual avaliado', [
                    'item_id' => $item->id,
                    'item_nome' => $item->nome,
                    'ano_corrente' => $anoCorrente,
                    'ja_pago' => $jaPago,
                ]);

                if (! $jaPago) {
                    $pendencias->push([
                        'item_pagavel_id' => $item->id,
                        'nome' => $item->nome,
                        'frequencia' => $item->frequencia,
                        'mes' => null,
                        'ano' => $anoCorrente,
                    ]);
                }
            }
        }

        $resultado = [
            'pendencias' => $pendencias->values()->all(),
            'total_pendencias' => $pendencias->count(),
            'pagos' => $pagosList,
            'total_pagos' => $totalPagos,
        ];

        Log::debug('[VerificadorPropinaService] resultado final', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => $resultado['total_pendencias'],
            'total_pagos' => $resultado['total_pagos'],
        ]);

        return $resultado;
    }

    public function estaEmDia(Aluno $aluno): bool
    {
        $resultado = $this->pendenciasDoAluno($aluno);
        return empty($resultado['pendencias']);
    }

    private function pendenciasMensais(ItemPagavel $item, Collection $pagos, Carbon $inicio, Carbon $fim): array
    {
        $pendencias = collect();
        $pagosDetalhados = collect();

        $cursor = $inicio->copy();

        while ($cursor->lte($fim)) {
            $mes = $cursor->month;
            $ano = $cursor->year;
            $pago = $pagos->contains(fn($p) => (int) $p->mes === $mes && (int) $p->ano === $ano);

            if ($pago) {
                $pagosDetalhados->push([
                    'item_pagavel_id' => $item->id,
                    'nome' => $item->nome,
                    'frequencia' => $item->frequencia,
                    'mes' => $mes,
                    'ano' => $ano,
                    'status' => 'pago',
                    'mes_ano' => "{$mes}/{$ano}",
                ]);
            } else {
                $pendencias->push([
                    'item_pagavel_id' => $item->id,
                    'nome' => $item->nome,
                    'frequencia' => $item->frequencia,
                    'mes' => $mes,
                    'ano' => $ano,
                ]);
            }

            $cursor->addMonth();
        }

        return [$pendencias, $pagosDetalhados];
    }
}