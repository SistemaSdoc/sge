<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\ItemPagavel;
use App\Models\PagamentoItem;
use App\Models\User;
use App\Notifications\PropinaEmAtrasoNotification;
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

        $turma->loadMissing(['cursoClasseTurno.cursoClasse']);

        $cursoClasseId = $turma->curso_classe_id
                        ?? $turma->cursoClasseTurno->curso_classe_id
                        ?? null;

        $classeId = $turma->classe_id
                    ?? $turma->cursoClasseTurno->cursoClasse->classe_id
                    ?? null;

        Log::debug('[VerificadorPropinaService] DADOS TURMA E ANO', [
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'turma_nome' => $turma->nome ?? 'N/A',
            'curso_classe_id' => $cursoClasseId,
            'classe_id' => $classeId,
            'ano_lectivo_id' => $anoLectivo->id,
            'ano_lectivo_inicio' => (string) $anoLectivo->data_inicio,
            'ano_lectivo_fim' => (string) $anoLectivo->data_fim,
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

        if ($inicio->gt($fim)) {
            Log::warning('[VerificadorPropinaService] PERÍODO INVERTIDO — matrícula posterior ao fim do ano lectivo (ou ano lectivo já terminou)', [
                'aluno_id' => $aluno->id,
                'turma_id' => $turma->id,
                'data_matricula' => (string) $dataMatricula,
                'inicio_calculado' => (string) $inicio,
                'fim_calculado' => (string) $fim,
                'ano_lectivo_fim' => (string) $anoLectivo->data_fim,
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
            'modo' => $modo,
            'total' => $todosItens->count(),
        ]);

        $itensAplicaveis = $todosItens->filter(fn ($item) => $this->ehItemDeBloqueio($item));

        Log::debug('[VerificadorPropinaService] ITENS APÓS FILTRO BLOQUEIO', [
            'aluno_id' => $aluno->id,
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
                        'valor_base'      => (float) $item->valor,
                        'multa'           => 0.0,
                        'valor'           => (float) $item->valor,
                    ]);
                }
            }
        }

        $resultado = $pendencias->values()->all();

        Log::debug('[VerificadorPropinaService] RESULTADO FINAL', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => count($resultado),
            'valor_total_com_multas' => collect($resultado)->sum('valor'),
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
            $valores = $this->valorComMulta($item, $mes, $ano);

            $pendencias->push([
                'item_pagavel_id' => $item->id,
                'nome'            => $item->nome,
                'frequencia'      => $item->frequencia,
                'mes'             => $mes,
                'ano'             => $ano,
                'valor_base'      => $valores['valor_base'],
                'multa'           => $valores['multa'],
                'valor'           => $valores['valor'],
            ]);
        }

        $cursor->addMonth();
    }

    return $pendencias;
}

/**
 * Calcula o valor a pagar por um item mensal (ex: propina) num mês/ano
 * específico, já incluindo a multa por atraso se aplicável. Usado tanto
 * no cálculo de pendências (pendenciasMensais) como no fluxo de
 * registo de pagamento (PagamentoController), para que os dois locais
 * nunca fiquem dessincronizados quanto ao valor correto a cobrar.
 *
 * @return array{valor_base: float, multa: float, valor: float}
 */
public function valorComMulta(ItemPagavel $item, int $mes, int $ano): array
{
    $valorBase = (float) $item->valor;
    $multa = $this->calcularMulta($item, $mes, $ano);

    return [
        'valor_base' => $valorBase,
        'multa' => $multa,
        'valor' => $valorBase + $multa,
    ];
}

/**
 * Calcula a multa por atraso de um item mensal (ex: propina) num
 * mês/ano específico. Se o item não tiver multa_dias_tolerancia ou
 * multa_valor configurados, não há multa (retorna 0).
 *
 * Exemplo: multa_dias_tolerancia = 10 significa que o pagamento
 * pode ser feito até ao dia 10 do próprio mês sem multa. A partir
 * do dia 11 (inclusive), a multa passa a ser cobrada.
 */
private function calcularMulta(ItemPagavel $item, int $mes, int $ano): float
{
    if (! $item->multa_dias_tolerancia || ! $item->multa_valor) {
        return 0.0;
    }

    $dataLimite = Carbon::create($ano, $mes, 1)
        ->addDays($item->multa_dias_tolerancia - 1)
        ->endOfDay();

    $aplicaMulta = Carbon::now()->gt($dataLimite);

    Log::debug('[VerificadorPropinaService] calcularMulta', [
        'item_id' => $item->id,
        'item_nome' => $item->nome,
        'mes' => $mes,
        'ano' => $ano,
        'dias_tolerancia' => $item->multa_dias_tolerancia,
        'data_limite' => (string) $dataLimite,
        'hoje' => (string) Carbon::now(),
        'aplica_multa' => $aplicaMulta,
        'valor_multa_configurado' => (float) $item->multa_valor,
    ]);

    return $aplicaMulta ? (float) $item->multa_valor : 0.0;
}





    public function statusAlunos(Collection $alunos): Collection
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

    /**
     * Notifica o utilizador sobre propinas em atraso, evitando duplicar
     * quando o estado da dívida (nº de pendências + valor total, já com
     * multa) é o mesmo de uma notificação anterior — lida ou não.
     * Partilhado entre o middleware (acesso bloqueado), o controller
     * (pagamento anulado) e o comando agendado (aviso proactivo diário).
     */
    public function notificarSeNecessario(User $user, array $pendencias): void
    {
        if (empty($pendencias)) {
            return;
        }

        $totalPendencias = count($pendencias);
        $valorTotal = (float) collect($pendencias)->sum('valor');
        $multaTotal = (float) collect($pendencias)->sum('multa');
        $assinatura = md5($totalPendencias . '-' . $valorTotal);

        Log::debug('[VerificadorPropinaService] notificarSeNecessario', [
            'user_id' => $user->id,
            'total_pendencias' => $totalPendencias,
            'valor_total' => $valorTotal,
            'multa_total' => $multaTotal,
            'assinatura' => $assinatura,
        ]);

        $ultima = $user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->latest()
            ->first();

        if ($ultima && ($ultima->data['assinatura'] ?? null) === $assinatura) {
            Log::debug('[VerificadorPropinaService] notificação já existe para este estado — não duplica', [
                'user_id' => $user->id,
                'assinatura' => $assinatura,
                'notificacao_existente_id' => $ultima->id,
            ]);
            return;
        }

        $meses = collect($pendencias)
            ->filter(fn ($p) => $p['mes'] !== null)
            ->map(fn ($p) => self::MESES[$p['mes']] . '/' . $p['ano'])
            ->values()
            ->all();

        $user->notify(new PropinaEmAtrasoNotification($totalPendencias, $valorTotal, $multaTotal, $meses, $assinatura));

        Log::info('[VerificadorPropinaService] notificação criada', [
            'user_id' => $user->id,
            'total_pendencias' => $totalPendencias,
            'valor_total' => $valorTotal,
            'multa_total' => $multaTotal,
            'assinatura' => $assinatura,
        ]);
    }
}
