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

    public function pendenciasDoAluno(Aluno $aluno): array
    {
        Log::debug('[VerificadorPropinaService] INICIO pendenciasDoAluno', [
            'aluno_id' => $aluno->id,
            'aluno_nome' => $aluno->nome ?? 'N/A',
        ]);

        $turma = $aluno->turmaActual()->first();
        if (! $turma) {
            Log::debug('[VerificadorPropinaService] SEM TURMA ATUAL — retorna vazio', ['aluno_id' => $aluno->id]);
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
        \Log::debug('DEBUG pendencias', [
    'aluno_id' => $aluno->id,
    'turma_existe' => (bool) $turma,
    'ano_lectivo_turma' => $turma?->anoLectivo?->id,
]);
if (! $turma) {
    // sem turma associada — não há como calcular pendência real
    return [];
}

$anoLectivo = $turma->anoLectivo;

if (! $anoLectivo) {
    return [];
}

        $turma->loadMissing(['cursoClasseTurno.cursoClasse']);

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

// --- NOVO: aviso explícito quando o período é inválido ---
if ($inicio->gt($fim)) {
    Log::warning('[VerificadorPropinaService] PERÍODO INVERTIDO — matrícula posterior ao fim do ano lectivo (ou ano lectivo já terminou)', [
        'aluno_id' => $aluno->id,
        'turma_id' => $turma->id,
        'data_matricula' => (string) $dataMatricula,
        'inicio_calculado' => (string) $inicio,
        'fim_calculado' => (string) $fim,
        'ano_lectivo_fim' => (string) $anoLectivo->data_fim,
        'meses_entre'    => $inicio->diffInMonths($fim) + 1,
    ]);
    return [];
}


        $query = ItemPagavel::query()
            ->where('instituicao_id', $aluno->user->instituicao_id)
            ->ativos();

        if ($cursoClasseId || $classeId) {
            $query->where(function ($q) use ($cursoClasseId, $classeId) {
                $q->whereNull('curso_classe_id');

                if ($cursoClasseId) {
                    $q->orWhere('curso_classe_id', $cursoClasseId);
                }

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
        ]);

        $itensAplicaveis = $todosItens->filter(fn ($item) => $this->ehItemDeBloqueio($item));

        Log::debug('[VerificadorPropinaService] ITENS APÓS FILTRO BLOQUEIO', [
            'aluno_id'       => $aluno->id,
            'total_bloqueio' => $itensAplicaveis->count(),
        ]);

        if ($itensAplicaveis->isEmpty()) {
            Log::debug('[VerificadorPropinaService] NENHUM ITEM DE BLOQUEIO — retorna vazio', ['aluno_id' => $aluno->id]);
            return [];
        }

        $pagamentosExistentes = PagamentoItem::query()
            ->where('aluno_id', $aluno->id)
            ->whereHas('pagamento')
            ->get()
            ->groupBy('item_pagavel_id');

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
                        'nome'            => $item->nome,
                        'frequencia'      => $item->frequencia,
                        'mes'             => null,
                        'ano'             => $anoCorrente,
                        'valor'           => $item->valor, // <-- adicionado
                    ]);
                }
            }
        }

        $resultado = $pendencias->values()->all();

        Log::debug('[VerificadorPropinaService] RESULTADO FINAL', [
            'aluno_id'         => $aluno->id,
            'total_pendencias' => count($resultado),
        ]);

        return $resultado;
    }

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

        while ($cursor->lte($fim)) {
            $mes = $cursor->month;
            $ano = $cursor->year;

            $pago = $pagos->contains(fn ($p) => (int) $p->mes === $mes && (int) $p->ano === $ano);

            if (! $pago) {
                $pendencias->push([
                    'item_pagavel_id' => $item->id,
                    'nome'            => $item->nome,
                    'frequencia'      => $item->frequencia,
                    'mes'             => $mes,
                    'ano'             => $ano,
                    'valor'           => $item->valor, // <-- adicionado
                ]);
            }

            $cursor->addMonth();
        }

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