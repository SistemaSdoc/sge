<?php

namespace App\Services\Tenant\Pauta\Concerns;

trait ResolveSituacaoNota
{
    private function resolverSituacao(?float $media, ?string $situacao): string
    {
        if ($media === null) {
            return 'sem_notas';
        }

        return $situacao ?? 'incompleto';
    }

    private function arredondarNota(?float $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        return round((float) $valor, 0, PHP_ROUND_HALF_UP);
    }
}
