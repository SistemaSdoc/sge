<?php

namespace App\Services\Rupe;

use App\Models\SolicitacaoDocumento;

class RupeGeneratorManual implements RupeGeneratorInterface
{
    public function gerar(SolicitacaoDocumento $solicitacao): array
    {
        // Implementação manual / stub — gera valores placeholder.
        $valor = method_exists($solicitacao, 'valorDocumento') ? $solicitacao->valorDocumento() : null;

        return [
            'referencia' => null,
            'entidade' => null,
            'valor' => $valor,
            'gerado_em' => now(),
        ];
    }
}
