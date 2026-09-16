<?php

namespace App\Services\Tenant\AnoLectivo;

use App\Services\Central\AnoLectivoService;

/**
 * Resolver para o ano lectivo
 */
class AnoLectivoResolverService
{
    public function __construct(private readonly AnoLectivoService $anoLectivoService) {}

    /**
     * Pega o ano lectivo padrão com base na data actual.
     */
    public function obterAnoLectivoDefault(): ?string
    {
        return $this->anoLectivoService->defaultId();
    }
}
