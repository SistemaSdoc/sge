<?php

namespace App\Jobs\Tenant\Tutela;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Services\Tenant\Tutela\TutelaTenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tenta novamente a associação local de um vínculo já criado na central.
 */
class SincronizarAssociacaoTutela implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 3600;

    public function __construct(
        public string $tenantTuteladoId,
        public string $cursoTuteladoId,
        public string $sharedId,
    ) {}

    /**
     * Reassocia o curso dentro do tenant tutelado.
     */
    public function handle(TutelaTenantService $tenantService): void
    {
        Log::info('Iniciando job de sincronização de associação de tutela', [
            'shared_id' => $this->sharedId,
            'tenant_tutelado_id' => $this->tenantTuteladoId,
            'curso_tutelado_id' => $this->cursoTuteladoId,
            'attempt' => $this->attempts(),
        ]);
        $centralConnection = (string) config(
            'tenancy.database.central_connection',
            config('database.default'),
        );
        $tenant = Tenant::on($centralConnection)->findOrFail($this->tenantTuteladoId);
        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($this->sharedId);

        $tenant->run(function () use ($tenantService, $shared): void {
            $cursoTutelado = CursoTutelado::query()->findOrFail($this->cursoTuteladoId);

            $tenantService->associarTutelaExterna($cursoTutelado, $shared);
        });

        Log::info('Job de sincronização de associação de tutela completado com sucesso', [
            'shared_id' => $this->sharedId,
            'tenant_tutelado_id' => $this->tenantTuteladoId,
            'curso_tutelado_id' => $this->cursoTuteladoId,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva do job de sincronização de associação de tutela', [
            'shared_id' => $this->sharedId,
            'tenant_tutelado_id' => $this->tenantTuteladoId,
            'curso_tutelado_id' => $this->cursoTuteladoId,
            'attempt' => $this->attempts(),
            'exception' => $exception?->getMessage(),
        ]);

        if ($exception) {
            report($exception);
        }
    }

    public function uniqueId(): string
    {
        return $this->sharedId;
    }
}
