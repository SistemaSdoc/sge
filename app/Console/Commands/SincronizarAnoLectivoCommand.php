<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\AnoLectivoConsistencyService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

#[Signature('anoletivo:sincronizar')]
#[Description('Sincroniza o ano letivo activo e cria o próximo antecipadamente')]
class SincronizarAnoLectivoCommand extends Command
{
    public function handle(AnoLectivoConsistencyService $service): int
    {
        Tenant::all()->each(function (Tenant $tenant) use ($service): void {
            try {
                tenancy()->initialize($tenant);
                $service->sincronizar();
            } catch (TenantDatabaseDoesNotExistException $exception) {
                Log::warning('Skipping tenant without database during academic year synchronization.', [
                    'tenant_id' => $tenant->getTenantKey(),
                    'exception' => $exception,
                ]);
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        });

        $this->info('Sincronização de ano letivo concluída.');

        return 0;
    }
}
