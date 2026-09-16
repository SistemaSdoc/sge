<?php

namespace App\Services\Tenant;

use App\Services\Central\AnoLectivoService;
use Illuminate\Support\Facades\Log;

/**
 * Compatibilidade temporária para chamadas antigas durante a migração.
 *
 * A fonte de verdade é exclusivamente a base central. Este serviço não
 * escreve nem cria anos lectivos nas bases tenant.
 */
class AnoLectivoConsistencyService
{
    public function __construct(private readonly AnoLectivoService $anoLectivoService) {}

    public function sincronizar(): void
    {
        $anoLectivo = $this->anoLectivoService->current();

        Log::info('Ano lectivo central verificado; não existe sincronização local.', [
            'ano_lectivo_id' => $anoLectivo?->getKey(),
            'nome' => $anoLectivo?->nome,
        ]);
    }
}
