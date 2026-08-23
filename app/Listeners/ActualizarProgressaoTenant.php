<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Events\DatabaseCreated;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\DatabaseSeeded;

class ActualizarProgressaoTenant
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DatabaseCreated|DatabaseMigrated|DatabaseSeeded $event): void
    {
        $tenant = $event->tenant;

        // Mapeia os eventos para mensagens amigas
        $etapas = [
            DatabaseCreated::class => [
                'step' => 'base_dados_criada',
                'message' => 'Base de dados criada ✓',
                'percentage' => 25,
            ],
            DatabaseMigrated::class => [
                'step' => 'migrations_feitas',
                'message' => 'Migrations executadas ✓',
                'percentage' => 50,
            ],
            DatabaseSeeded::class => [
                'step' => 'dados_populados',
                'message' => 'Dados populados ✓',
                'percentage' => 75,
            ],
        ];

        $tipoEvento = $event::class;

        if (isset($etapas[$tipoEvento])) {
            // Guarda no cache (memória rápida)
            Cache::put(
                "progresso_tenant_{$tenant->id}",
                array_merge($etapas[$tipoEvento], ['status' => 'em_progresso']),
                now()->addHour() // Expira em 1 hora
            );
        }
    }
}
