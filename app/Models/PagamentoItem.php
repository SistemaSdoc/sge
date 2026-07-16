<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoItem extends Model
{
    use HasUuid;

    protected $table = 'pagamento_itens';

    protected $fillable = ['pagamento_id', 'item_pagavel_id', 'aluno_id', 'mes', 'ano', 'valor', 'valor_esperado'];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_esperado' => 'decimal:2',
    ];

    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(Pagamento::class);
    }

    public function itemPagavel(): BelongsTo
    {
        return $this->belongsTo(ItemPagavel::class);
    }

    /**
     * Calcula o saldo de um item para um aluno num período específico,
     * somando todas as parcelas já pagas (excluindo pagamentos soft-deleted).
     *
     * Retorna null em 'esperado'/'saldo' quando ainda não há nenhuma parcela
     * registada — nesse caso o chamador deve usar o valor actual do catálogo
     * (ItemPagavel::valor) como valor esperado.
     */
    public static function saldo(string $alunoId, string $itemPagavelId, int $ano, int $mes): array
    {
        $query = static::whereHas('pagamento')
            ->where('aluno_id', $alunoId)
            ->where('item_pagavel_id', $itemPagavelId)
            ->where('ano', $ano)
            ->where('mes', $mes);

        $pago = (float) $query->sum('valor');
        $esperado = $query->max('valor_esperado');

        if ($esperado === null) {
            return [
                'pago' => 0.0,
                'esperado' => null,
                'saldo' => null,
                'status' => 'pendente',
            ];
        }

        $esperado = (float) $esperado;
        $saldo = round($esperado - $pago, 2);

        return [
            'pago' => $pago,
            'esperado' => $esperado,
            'saldo' => $saldo,
            'status' => $saldo <= 0 ? 'pago' : 'parcial',
        ];
    }

    /**
     * Resolve o valor_esperado a usar numa nova parcela: reutiliza o snapshot
     * já gravado se já existir alguma parcela para este aluno+item+período,
     * senão usa o valor actual do catálogo (primeira parcela).
     */
    public static function valorEsperadoParaNovaParcela(
        string $alunoId,
        string $itemPagavelId,
        int $ano,
        int $mes,
        float $valorCatalogoAtual
    ): float {
        $esperadoExistente = static::whereHas('pagamento')
            ->where('aluno_id', $alunoId)
            ->where('item_pagavel_id', $itemPagavelId)
            ->where('ano', $ano)
            ->where('mes', $mes)
            ->value('valor_esperado');

        return $esperadoExistente !== null ? (float) $esperadoExistente : $valorCatalogoAtual;
    }
}
