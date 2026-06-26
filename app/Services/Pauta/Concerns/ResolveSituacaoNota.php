<?php

namespace App\Services\Pauta\Concerns;

trait ResolveSituacaoNota
{
    private function resolverSituacao(?float $media, ?string $situacao): string
    {
        if ($media === null) {
            return 'sem_notas';
        }

        return $situacao ?? 'incompleto';
    }
}
