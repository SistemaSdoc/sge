<?php

namespace App\Services\Tenant\AnoLectivo;

use App\Models\Tenant\AnoLectivo;

/**
 * Resolver para o ano lectivo
 */
class AnoLectivoResolverService
{
    /**
     * Pega o ano lectivo padrão com base na data actual.
     */
    public function obterAnoLectivoDefault(): ?string
    {
        return AnoLectivo::query()
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->orderByDesc('data_inicio')
            ->first()?->id
            ?? AnoLectivo::query()
                ->where('data_inicio', '>', now())
                ->orderBy('data_inicio')
                ->first()?->id
            ?? AnoLectivo::query()
                ->orderByDesc('data_inicio')
                ->first()?->id;
    }
}
