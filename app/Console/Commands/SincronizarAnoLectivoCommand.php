<?php

namespace App\Console\Commands;

use App\Services\AnoLectivoConsistencyService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('anoletivo:sincronizar')]
#[Description('Sincroniza o ano letivo activo e cria o próximo antecipadamente')]
class SincronizarAnoLectivoCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AnoLectivoConsistencyService $service): int
    {

        $service->sincronizar();

        $this->info('Sincronização de ano letivo concluída.');

        return 0;
    }
}
