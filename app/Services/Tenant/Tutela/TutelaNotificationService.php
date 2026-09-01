<?php

namespace App\Services\Tenant\Tutela;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use App\Notifications\SolicitacaoTutelaNotification;
use App\Services\Central\TenantService;
use Illuminate\Support\Facades\Log;

/**
 * Envia notificações de tutela no tenant tutor.
 */
class TutelaNotificationService
{
    /**
     * Recebe o serviço usado para localizar instituições noutros tenants.
     */
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * Envia a notificação ao administrador do tenant tutor.
     *
     * A procura do administrador e a gravação da notificação acontecem dentro
     * do contexto do tenant tutor.
     */
    public function notificarNovaSolicitacao(CursoTuteladoShared $shared): void
    {
        Log::debug('Iniciando notificação de nova solicitação de tutela', [
            'shared_id' => $shared->id,
            'tenant_tutor_id' => $shared->tenant_tutor_id,
            'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            'curso_nome' => $shared->curso_nome,
        ]);

        $tenantTutor = Tenant::query()->find($shared->tenant_tutor_id);
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);

        if (! $tenantTutor || ! $tenantTutelado || ! $tenantTutor->admin_user_id) {
            Log::warning('Notificação não enviada: dados incompletos', [
                'shared_id' => $shared->id,
                'tenant_tutor_existe' => (bool) $tenantTutor,
                'tenant_tutelado_existe' => (bool) $tenantTutelado,
                'admin_user_id' => $tenantTutor?->admin_user_id,
            ]);

            return;
        }

        $instituicaoTutelada = $this->tenantService->getInstituicao($tenantTutelado);

        if (! $instituicaoTutelada) {
            Log::warning('Notificação não enviada: instituição tutelada não encontrada', [
                'shared_id' => $shared->id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            ]);

            return;
        }

        $tenantTutor->run(function () use (
            $tenantTutor,
            $instituicaoTutelada,
            $shared
        ): void {
            $admin = User::query()->find($tenantTutor->admin_user_id);

            if (! $admin) {
                Log::warning('Notificação não enviada: admin user não encontrado', [
                    'shared_id' => $shared->id,
                    'tenant_tutor_id' => $tenantTutor->id,
                    'admin_user_id' => $tenantTutor->admin_user_id,
                ]);

                return;
            }

            Log::info('Enviando notificação de solicitação de tutela', [
                'shared_id' => $shared->id,
                'tenant_tutor_id' => $tenantTutor->id,
                'admin_email' => $admin->email,
                'instituicao_tutelada' => $instituicaoTutelada->nome,
                'curso_nome' => $shared->curso_nome,
            ]);

            $admin->notify(new SolicitacaoTutelaNotification(
                instituicaoTutelada: $instituicaoTutelada->nome,
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                url: $this->url($tenantTutor, (string) $shared->getKey()),
            ));

            Log::info('Notificação de solicitação de tutela enviada com sucesso', [
                'shared_id' => $shared->id,
                'admin_email' => $admin->email,
            ]);
        });
    }

    /**
     * Constrói o URL absoluto da notificação no domínio do tenant tutor.
     */
    private function url(Tenant $tenant, string $sharedId): string
    {
        $domain = $tenant->domains()->first()?->domain;
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

        return $domain
            ? "{$scheme}://{$domain}/dashboard/notificacoes/tutela/{$sharedId}"
            : url("/dashboard/notificacoes/tutela/{$sharedId}");
    }
}
