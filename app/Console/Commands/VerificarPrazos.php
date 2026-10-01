<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\PrazoProva;
use App\Notifications\PrazoProvaNotificacao;
use App\Services\Tenant\PrazoNotificacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerificarPrazos extends Command
{
    protected $signature = 'prazos:verificar';

    protected $description = 'Avisa prazos a expirar (30 min) e marca os expirados';

    public function handle(PrazoNotificacaoService $notificacao): int
    {
        $tenants = Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant encontrado.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $this->info("▶ Tenant: {$tenant->getTenantKey()}");

                $this->avisarPrazosAExpirar($notificacao);
                $this->marcarExpirados($notificacao);

                tenancy()->end();
            } catch (Throwable $e) {
                // Garante que a tenancy é sempre encerrada
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                Log::warning('Falha ao verificar prazos do tenant', [
                    'tenant_id' => $tenant->getTenantKey(),
                    'erro'      => $e->getMessage(),
                    'linha'     => $e->getLine(),
                    'ficheiro'  => $e->getFile(),
                ]);

                $this->warn("✗ Tenant {$tenant->getTenantKey()} falhou: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Avisa prazos que expiram nos próximos 30 minutos.
     */
    private function avisarPrazosAExpirar(PrazoNotificacaoService $notificacao): void
    {
        $agora = now();
        $daquiA30Min = now()->addMinutes(30);

        $prazos = PrazoProva::where('status', 'aberto')
            ->where('aviso_expiracao_enviado', false)
            ->whereBetween('data_limite', [$agora, $daquiA30Min])
            ->with(['disciplina', 'classe'])
            ->get();

        if ($prazos->isEmpty()) {
            return;
        }

        foreach ($prazos as $prazo) {
            try {
                $notificacao->notificarProfessores(
                    $prazo,
                    PrazoProvaNotificacao::TIPO_A_EXPIRAR
                );

                $prazo->update(['aviso_expiracao_enviado' => true]);

                $this->line(sprintf(
                    '  Aviso enviado: %s (expira às %s)',
                    $prazo->titulo,
                    $prazo->data_limite->format('H:i')
                ));

                Log::info('Aviso de expiração enviado', [
                    'prazo_id' => $prazo->id,
                ]);
            } catch (Throwable $e) {
                Log::error('Falha ao enviar aviso de expiração', [
                    'prazo_id' => $prazo->id,
                    'erro'     => $e->getMessage(),
                ]);

                $this->warn("  ✗ Falha no aviso do prazo #{$prazo->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Marca prazos expirados e notifica.
     */
    private function marcarExpirados(PrazoNotificacaoService $notificacao): void
    {
        $prazos = PrazoProva::where('status', 'aberto')
            ->where('data_limite', '<', now())
            ->with(['disciplina', 'classe'])
            ->get();

        if ($prazos->isEmpty()) {
            return;
        }

        foreach ($prazos as $prazo) {
            try {
                // 1. Notificar professores (expirado)
                $notificacao->notificarProfessores(
                    $prazo,
                    PrazoProvaNotificacao::TIPO_EXPIRADO
                );

                // 2. Notificar diretores com estatísticas
                $notificacao->notificarDiretoresPrazoExpirado($prazo);

                // 3. Atualizar o status
                $prazo->update(['status' => 'expirado']);

                $this->line("   Prazo expirado: {$prazo->titulo}");

                Log::info('Prazo expirado', [
                    'prazo_id' => $prazo->id,
                ]);
            } catch (Throwable $e) {
                Log::error('Falha ao processar prazo expirado', [
                    'prazo_id' => $prazo->id,
                    'erro'     => $e->getMessage(),
                ]);

                $this->warn("  ✗ Falha no prazo #{$prazo->id}: {$e->getMessage()}");
            }
        }
    }
}