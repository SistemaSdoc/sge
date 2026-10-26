<?php

namespace App\Console\Commands;

use App\Services\Central\AnoLectivoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('anoletivo:sincronizar')]
#[Description('Verifica o ano lectivo activo na base central')]
class SincronizarAnoLectivoCommand extends Command
{
    public function handle(AnoLectivoService $service): int
    {
        $service->sincronizarEstado();
        $anoLectivo = $service->current();

        if ($anoLectivo === null) {
            $this->warn('Nenhum ano lectivo activo encontrado na central.');

            return self::SUCCESS;
        }

        $this->info("Ano lectivo central activo: {$anoLectivo->nome}.");

        return self::SUCCESS;
    }
}
