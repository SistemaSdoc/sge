<?php

namespace App\Actions\Tenant\Notificacao\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Services\Tenant\Tutela\TutelaNotificationService;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Decide a troca de tutela pela instituição tutora anterior.
 */
final class DecidirTrocaTutela
{
    /**
     * Recebe o serviço que envia notificações sobre a troca.
     */
    public function __construct(
        private readonly TutelaNotificationService $notificacaoService,
    ) {}

    /**
     * Aprova ou rejeita uma troca de tutela pendente.
     *
     * @return array{type: string, message: string}
     */
    public function handle(
        DatabaseNotification $notification,
        CursoTuteladoShared $shared,
        string $centralConnection,
        TutelaStatus $status,
        string $tenantActualId,
    ): array {
        $tenantTutorAnteriorId = $notification->data['tenant_tutor_anterior_id'] ?? null;

        abort_unless((string) $tenantTutorAnteriorId === $tenantActualId, 403);
        abort_if($shared->status !== TutelaStatus::PENDENTE_TROCA, 422, 'Esta troca já foi decidida.');

        $decisaoStatus = $status === TutelaStatus::ACTIVO
            ? 'aprovada_instituicao_anterior'
            : 'rejeitada';

        $notification->data = array_merge(
            $notification->data,
            ['status' => $decisaoStatus]
        );

        $notification->save();

        if ($status === TutelaStatus::ACTIVO) {
            $shared->update(['status' => TutelaStatus::PENDENTE]);
            $notification->markAsRead();

            $this->notificacaoService->aprovarTrocaTutela($shared);

            $this->notificacaoService->notificarResultadoTroca(
                $shared,
                (string) $tenantTutorAnteriorId,
                'aprovada',
                'instituicao_anterior',
            );

            return [
                'type' => 'success',
                'message' => 'A instituição anterior aprovou a troca. Aguardando aprovação da nova instituição.',
            ];
        }

        $shared->update(['status' => TutelaStatus::REJEITADO]);
        $sharedAnteriorId = $notification->data['curso_tutelado_shared_anterior_id'] ?? null;

        if ($sharedAnteriorId) {
            CursoTuteladoShared::on($centralConnection)
                ->whereKey($sharedAnteriorId)
                ->where('status', TutelaStatus::ENCERRADO)
                ->update(['status' => TutelaStatus::ACTIVO]);
        }

        $this->notificacaoService->notificarRejeicaoTroca(
            $shared,
            (string) $tenantTutorAnteriorId,
        );

        $notification->markAsRead();

        return [
            'type' => 'warning',
            'message' => 'Troca de tutela rejeitada.',
        ];
    }
}
