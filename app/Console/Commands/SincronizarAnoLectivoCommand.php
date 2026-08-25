<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\AnoLectivoConsistencyService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('anoletivo:sincronizar')]
#[Description('Sincroniza o ano letivo activo e cria o próximo antecipadamente')]
class SincronizarAnoLectivoCommand extends Command
{
    public function handle(AnoLectivoConsistencyService $service): int
    {
        Tenant::all()->each(function ($tenant) use ($service) {
            tenancy()->initialize($tenant);
            $service->sincronizar();
            tenancy()->end();
        });

        $this->info('Sincronização de ano letivo concluída.');
        return 0;
    }
}