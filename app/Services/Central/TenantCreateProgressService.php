<?php

namespace App\Services\Central;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCreateProgressService
{
    private const CACHE_STORE = 'database';

    private const CACHE_TTL = 3600; // 1 hora

    private const PROGRESS_KEY_PREFIX = 'progresso_tenant_';

    private const MAX_STREAM_DURATION = 600; // 10 minutos

    private const HEARTBEAT_INTERVAL = 500000; // 500ms

    /**
     * Inicia o stream SSE de progresso do tenant
     */
    public function streamProgress(Tenant $tenant): StreamedResponse
    {
        return response()->stream(
            function () use ($tenant) {
                $this->sendProgressStream($tenant);
            },
            200,
            $this->getStreamHeaders()
        );
    }

    /**
     * Inicializa o progresso de um tenant a ser criado
     */
    public function initialize(Tenant $tenant): void
    {
        $this->save($tenant, [
            'etapa' => 'iniciando',
            'mensagem' => 'A iniciar criação do tenant...',
            'percentagem' => 0,
            'status' => 'em_progresso',
        ]);
    }

    /**
     * Guarda o progresso no cache
     */
    public function save(Tenant $tenant, array $progress): void
    {
        Cache::store(self::CACHE_STORE)->put(
            $this->getCacheKey($tenant),
            $progress,
            now()->addSeconds(self::CACHE_TTL)
        );
    }

    /**
     * Obtém o progresso do cache
     */
    public function get(Tenant $tenant): ?array
    {
        return Cache::store(self::CACHE_STORE)->get($this->getCacheKey($tenant));
    }

    /**
     * Limpa o progresso do cache
     */
    public function clear(Tenant $tenant): void
    {
        Cache::store(self::CACHE_STORE)->forget($this->getCacheKey($tenant));
    }

    /**
     * Envia o stream SSE para o cliente
     */
    private function sendProgressStream(Tenant $tenant): void
    {
        $startTime = time();

        while (time() - $startTime < self::MAX_STREAM_DURATION) {
            $progress = $this->get($tenant);

            if ($progress) {
                echo 'data: '.json_encode($progress)."\n\n";

                if (in_array($progress['status'], ['concluido', 'erro'])) {
                    break;
                }
            } else {
                echo ": heartbeat\n\n";
            }

            ob_flush();
            flush();
            usleep(self::HEARTBEAT_INTERVAL);
        }
    }

    /**
     * Headers necessários para SSE
     */
    private function getStreamHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];
    }

    /**
     * Gera a chave do cache para um tenant
     */
    private function getCacheKey(Tenant $tenant): string
    {
        return self::PROGRESS_KEY_PREFIX.$tenant->id;
    }
}
