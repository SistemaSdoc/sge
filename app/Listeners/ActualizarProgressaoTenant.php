<?php

namespace App\Listeners;

use App\Models\Central\Tenant;
use App\Services\Central\TenantCreateProgressService;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\DatabaseCreated;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\DatabaseSeeded;

class ActualizarProgressaoTenant
{
    /**
     * Mapeia eventos de base de dados para etapas de progresso do tenant.
     */
    public function handle(DatabaseCreated|DatabaseMigrated|DatabaseSeeded $event): void
    {
        $tenant = Tenant::find($event->tenant->id);

        if (! $tenant) {
            Log::warning("Tenant não encontrado para ID: {$event->tenant->id}");

            return;
        }

        $eventClass = $event::class;
        Log::info("Listener disparado: {$eventClass}");

        $etapas = $this->getProgressStages();
        $tipoEvento = $event::class;

        if (isset($etapas[$tipoEvento])) {
            Log::info("Atualizando progresso para {$etapas[$tipoEvento]['mensagem']}");

            app(TenantCreateProgressService::class)->save(
                $tenant,
                [...$etapas[$tipoEvento], 'status' => 'em_progresso']
            );
        }
    }

    /**
     * Retorna o mapeamento de eventos para etapas de progresso.
     */
    private function getProgressStages(): array
    {
        return [
            DatabaseCreated::class => [
                'etapa' => 'base_dados_criada',
                'mensagem' => 'Base de dados criada ✓',
                'percentagem' => 25,
            ],
            DatabaseMigrated::class => [
                'etapa' => 'migrations_feitas',
                'mensagem' => 'Migrations executadas ✓',
                'percentagem' => 50,
            ],
            DatabaseSeeded::class => [
                'etapa' => 'dados_populados',
                'mensagem' => 'Dados populados ✓',
                'percentagem' => 75,
            ],
        ];
    }
}
