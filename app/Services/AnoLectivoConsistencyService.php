<?php

namespace App\Services;

use App\Models\AnoLectivo;
use Illuminate\Support\Facades\Log;

class AnoLectivoConsistencyService
{
    public function sincronizar(): void
    {
        Log::info('ano-lectivo.sincronizar.iniciado', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        $anoLectivoAtual = AnoLectivo::activo();

        Log::info('ano-lectivo.sincronizar.estado_actual', [
            'ano_lectivo_activo_id' => $anoLectivoAtual?->id,
            'ano_lectivo_activo_nome' => $anoLectivoAtual?->nome ?? null,
        ]);

        $desactivados = AnoLectivo::query()
            ->where('activo', true)
            ->update(['activo' => false]);

        Log::info('ano-lectivo.sincronizar.desactivados', [
            'total_desactivados' => $desactivados,
        ]);

        if ($anoLectivoAtual === null) {
            Log::warning('ano-lectivo.sincronizar.sem_ano_activo', [
                'mensagem' => 'Nenhum ano lectivo activo encontrado. Sincronização terminada sem reactivar nenhum registo.',
            ]);

            return;
        }

        $anoLectivoAtual->update(['activo' => true]);

        Log::info('ano-lectivo.sincronizar.concluido', [
            'ano_lectivo_id' => $anoLectivoAtual->id,
            'ano_lectivo_nome' => $anoLectivoAtual->nome ?? null,
            'reactivado' => true,
        ]);
    }
}
