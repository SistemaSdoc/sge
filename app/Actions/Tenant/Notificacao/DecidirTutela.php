<?php

namespace App\Actions\Tenant\Notificacao;

use App\Actions\Tenant\Notificacao\Tutela\DecidirConversaoTutelaPropria;
use App\Actions\Tenant\Notificacao\Tutela\DecidirSolicitacaoTutela;
use App\Actions\Tenant\Notificacao\Tutela\DecidirTrocaTutela;
use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\User;
use App\Services\Tenant\NotificacaoService;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Encaminha uma decisão de tutela para o fluxo correspondente ao tipo de notificação.
 */
final class DecidirTutela
{
    /**
     * Recebe as dependências dos fluxos de decisão.
     */
    public function __construct(
        private readonly NotificacaoService $notificacaoService,
        private readonly DecidirConversaoTutelaPropria $decidirConversaoTutelaPropria,
        private readonly DecidirTrocaTutela $decidirTrocaTutela,
        private readonly DecidirSolicitacaoTutela $decidirSolicitacaoTutela,
    ) {}

    /**
     * Decide uma notificação de tutela pertencente ao utilizador informado.
     */
    public function handle(
        User $user,
        string $notificationId,
        TutelaStatus $status
    ): array {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($notificationId);
        $type = $notification->data['tipo'] ?? null;

        abort_unless(in_array($type, [
            'solicitacao_tutela',
            'troca_tutela',
            'conversao_tutela_propria',
        ], true), 404);

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $sharedId = $notification->data['curso_tutelado_shared_id'] ?? null;

        if (! $sharedId) {
            $sharedId = $this->notificacaoService->resolverSharedIdDaNotificacao(
                $notification,
                $centralConnection,
            );
        }

        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($sharedId);
        $tenantId = (string) tenancy()->tenant->getTenantKey();

        $toast = match ($type) {
            'conversao_tutela_propria' => $this->decidirConversaoTutelaPropria->handle(
                notification: $notification,
                shared: $shared,
                centralConnection: $centralConnection,
                status: $status,
                tenantActualId: $tenantId,
            ),
            'troca_tutela' => $this->decidirTrocaTutela->handle(
                notification: $notification,
                shared: $shared,
                centralConnection: $centralConnection,
                status: $status,
                tenantActualId: $tenantId,
            ),
            'solicitacao_tutela' => $this->decidirSolicitacaoTutela->handle(
                notification: $notification,
                shared: $shared,
                centralConnection: $centralConnection,
                status: $status,
                tenantTutorId: $tenantId,
            ),
            default => abort(404),
        };

        return [
            'notificationId' => (string) $notification->getKey(),
            'toast' => $toast,
        ];
    }
}
