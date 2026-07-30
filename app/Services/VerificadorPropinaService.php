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
    /**
     * Só itens cujo nome corresponda a isto entram no cálculo de bloqueio.
     * Ajusta a lista se tiveres outras variações de nome (ex: "Mensalidade").
     */
    private const TERMOS_BLOQUEIO = ['propina', 'propinas'];

    /**
     * Devolve a lista de pendências do aluno (só itens de bloqueio, ex: propina).
     * Array vazio = está em dia.
     *
     * Cada pendência: ['item_pagavel_id', 'nome', 'frequencia', 'mes' => int|null, 'ano' => int]
     */
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
        $dataMatricula = $aluno->data_matricula ?? $aluno->created_at;
        $inicioAno = Carbon::parse($anoLectivo->data_inicio)->startOfMonth();
        $inicio = $dataMatricula ? Carbon::parse($dataMatricula)->startOfMonth() : $inicioAno;
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
            'ano_lectivo_data_inicio' => (string) $anoLectivo->data_inicio,
            'ano_lectivo_data_fim' => (string) $anoLectivo->data_fim,
            'data_matricula' => (string) $dataMatricula,
            'periodo_cobranca_inicio' => (string) $inicio,
            'periodo_cobranca_fim' => (string) $fim,
        ]);

        $todosItens = ItemPagavel::query()
            ->where('instituicao_id', $aluno->user->instituicao_id)
            ->ativos()
            ->where(function ($q) use ($turma) {
                $q->whereNull('curso_classe_id')
                    ->orWhere('curso_classe_id', $turma->curso_classe_id);
            })
            ->get();

        // Só os itens de "propina" entram no cálculo de bloqueio.
        // Os restantes (uniforme, material, excursão, etc.) ficam de fora —
        // o aluno pode continuar a vê-los/pagá-los sem ficar bloqueado por eles.
        $itensAplicaveis = $todosItens->filter(
            fn (ItemPagavel $item) => $this->ehItemDeBloqueio($item)
        );

        Log::debug('[VerificadorPropinaService] itens pagáveis (total vs bloqueio)', [
            'aluno_id' => $aluno->id,
            'total_itens' => $todosItens->count(),
            'itens_bloqueio' => $itensAplicaveis->pluck('nome', 'id')->toArray(),
            'itens_ignorados' => $todosItens->diff($itensAplicaveis)->pluck('nome', 'id')->toArray(),
        ]);

        if ($itensAplicaveis->isEmpty()) {
            Log::debug('[VerificadorPropinaService] nenhum item de propina configurado — não barra', [
                'aluno_id' => $aluno->id,
            ]);
            return [];
        }

        $pagamentosExistentes = PagamentoItem::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('pagamento') // garante que não foi anulado/soft-deleted
            ->get()
            ->groupBy('item_pagavel_id');

        Log::debug('[VerificadorPropinaService] pagamentos existentes do aluno', [
            'aluno_id' => $aluno->id,
            'total_registos' => $pagamentosExistentes->flatten()->count(),
            'por_item' => $pagamentosExistentes->map->count()->toArray(),
        ]);

        $pendencias = collect();

        foreach ($itensAplicaveis as $item) {
            $pagosDoItem = $pagamentosExistentes->get($item->id, collect());

            if ($item->frequencia === 'mensal') {
                $pendenciasDoItem = $this->pendenciasMensais($item, $pagosDoItem, $inicio, $fim);

                Log::debug('[VerificadorPropinaService] propina mensal avaliada', [
                    'item_id' => $item->id,
                    'item_nome' => $item->nome,
                    'meses_pagos' => $pagosDoItem->pluck('mes')->toArray(),
                    'meses_em_falta' => $pendenciasDoItem->map(fn ($p) => "{$p['mes']}/{$p['ano']}")->toArray(),
                ]);

                $pendencias = $pendencias->merge($pendenciasDoItem);
            } else {
                // 'unica' ou 'anual': basta existir um pagamento no ano lectivo corrente
                $anoCorrente = $anoLectivo->data_inicio->year;
                $jaPago = $pagosDoItem->where('ano', $anoCorrente)->isNotEmpty();

                Log::debug('[VerificadorPropinaService] propina única/anual avaliada', [
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

        $resultado = $pendencias->values()->all();

        Log::debug('[VerificadorPropinaService] resultado final', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => count($resultado),
            'pendencias' => $resultado,
        ]);

        return $resultado;
    }

    public function estaEmDia(Aluno $aluno): bool
    {
        return empty($this->pendenciasDoAluno($aluno));
    }

    private function ehItemDeBloqueio(ItemPagavel $item): bool
    {
        $nome = Str::lower(Str::ascii($item->nome)); // remove acentos: "propinas" == "propinás"

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

        while ($cursor->lte($fim)) {
            $mes = $cursor->month;
            $ano = $cursor->year;
            $pago = $pagos->contains(fn ($p) => (int) $p->mes === $mes && (int) $p->ano === $ano);

            if (! $pago) {
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

        return $pendencias;
    }
}