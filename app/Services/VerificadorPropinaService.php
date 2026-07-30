<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\PagamentoItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VerificadorPropinaService
{
    /**
     * Termos que identificam itens de bloqueio (ex: propina).
     * Ajuste conforme necessário.
     */
    private const TERMOS_BLOQUEIO = ['propina', 'propinas'];

    /**
     * Retorna a lista de pendências do aluno (somente itens de bloqueio).
     * Array vazio = aluno em dia.
     *
     * @return array<int, array{item_pagavel_id: string, nome: string, frequencia: string, mes: int|null, ano: int}>
     */
    public function pendenciasDoAluno(Aluno $aluno): array
    {
        $turma = $aluno->turmaActual()->first();

        if (! $turma) {
            return [];
        }

        $anoLectivo = $turma->anoLectivo;

        if (! $anoLectivo) {
            return [];
        }

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

        // --- Query de itens pagáveis com fallback ---
        $query = ItemPagavel::query()
            ->where('instituicao_id', $aluno->user->instituicao_id)
            ->ativos();

        // Se a turma tem algum vínculo (curso_classe ou classe), usamos as condições de associação
        if ($turma->curso_classe_id || $turma->classe_id) {
            $query->where(function ($q) use ($turma) {
                // 1. Globais
                $q->whereNull('curso_classe_id');

                // 2. Diretamente vinculado ao curso_classe da turma
                if ($turma->curso_classe_id) {
                    $q->orWhere('curso_classe_id', $turma->curso_classe_id);
                }

                // 3. Vinculado a um curso_classe cuja classe_id seja igual à da turma
                if ($turma->classe_id) {
                    $q->orWhereExists(function ($sub) use ($turma) {
                        $sub->from('cursos_classes')
                            ->whereColumn('cursos_classes.id', 'itens_pagaveis.curso_classe_id')
                            ->where('cursos_classes.classe_id', $turma->classe_id);
                    });
                }
            });
        } else {
            // Fallback: turma sem classe e sem curso_classe → buscar TODOS os itens da instituição
            // (não adicionamos restrição extra)
        }

        $todosItens = $query->get();

        // --- Filtra apenas os itens de bloqueio (propina) ---
        $itensAplicaveis = $todosItens->filter(
            fn (ItemPagavel $item) => $this->ehItemDeBloqueio($item)
        );

        if ($itensAplicaveis->isEmpty()) {
            return [];
        }

        // --- Pagamentos existentes do aluno ---
        $pagamentosExistentes = PagamentoItem::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id');

        // --- Cálculo das pendências ---
        $pendencias = collect();

        foreach ($itensAplicaveis as $item) {
            $pagosDoItem = $pagamentosExistentes->get($item->id, collect());

            if ($item->frequencia === 'mensal') {
                $pendenciasDoItem = $this->pendenciasMensais($item, $pagosDoItem, $inicio, $fim);
                $pendencias = $pendencias->merge($pendenciasDoItem);
            } else {
                $anoCorrente = $anoLectivo->data_inicio->year;
                $jaPago = $pagosDoItem->where('ano', $anoCorrente)->isNotEmpty();
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

        return $pendencias->values()->all();
    }

    /**
     * Verifica se o aluno está em dia (sem pendências).
     */
    public function estaEmDia(Aluno $aluno): bool
    {
        return empty($this->pendenciasDoAluno($aluno));
    }

    /**
     * Determina se o item deve ser considerado para bloqueio (com base no nome).
     */
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

    /**
     * Calcula pendências para itens de frequência mensal.
     *
     * @param Collection<int, PagamentoItem> $pagos
     * @return Collection<int, array>
     */
    private function pendenciasMensais(ItemPagavel $item, Collection $pagos, Carbon $inicio, Carbon $fim): Collection
    {
        $pendencias = collect();
        $cursor = $inicio->copy();

        while ($cursor->lte($fim)) {
            $mes = $cursor->month;
            $ano = $cursor->year;

            $pago = $pagos->contains(fn ($p) =>
                (int) $p->mes === $mes && (int) $p->ano === $ano
            );

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

    public function statusAlunos(\Illuminate\Support\Collection $alunos): \Illuminate\Support\Collection
    {
        return $alunos->map(function (Aluno $aluno) {
            $pendencias = $this->pendenciasDoAluno($aluno);

            return [
                'aluno_id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'em_dia' => empty($pendencias),
                'pendencias' => $pendencias,
            ];
        });
    }
}