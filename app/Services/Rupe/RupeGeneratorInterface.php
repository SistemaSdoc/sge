<?php

namespace App\Services\Rupe;

use App\Models\SolicitacaoDocumento;

interface RupeGeneratorInterface
{
    /**
     * Gerar um rupe para a solicitação.
     * Retorna um array com chaves: referencia, entidade, valor e opcionalmente gerado_em (DateTime|string)
     */
    public function gerar(SolicitacaoDocumento $solicitacao): array;
}
