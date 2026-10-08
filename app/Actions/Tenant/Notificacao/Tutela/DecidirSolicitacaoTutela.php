<?php

namespace App\Actions\Tenant\Notificacao\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Services\Tenant\Tutela\TutelaNotificationService;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Decide uma solicitação inicial de tutela externa.
 */
final class DecidirSolicitacaoTutela
{
    /**
     * Recebe o serviço que envia notificações sobre o resultado.
     */
    public function __construct(
        private readonly TutelaNotificationService $notificacaoService,
    ) {}

    /**
     * Aprova ou rejeita uma solicitação inicial de tutela.
     *
     * @return array{type: string, message: string}
     */
    public function handle(
        DatabaseNotification $notification,
        CursoTuteladoShared $shared,
        string $centralConnection,
        TutelaStatus $status,
        string $tenantTutorId,
    ): array {
        abort_unless((string) $shared->tenant_tutor_id === $tenantTutorId, 403);
        abort_if($shared->status !== TutelaStatus::PENDENTE, 422, 'Esta solicitação já foi decidida.');

        $shared->update(['status' => $status]);

        if (($notification->data['troca_tutela_final'] ?? false) && $status === TutelaStatus::ACTIVO) {
            $sharedAnteriorId = $notification->data['curso_tutelado_shared_anterior_id'] ?? null;

            if ($sharedAnteriorId) {
                CursoTuteladoShared::on($centralConnection)
                    ->whereKey($sharedAnteriorId)
                    ->where('status', TutelaStatus::ACTIVO)
                    ->update(['status' => TutelaStatus::ENCERRADO]);
            }

            $tenantTuteladoId = (string) $shared->tenant_tutelado_id;

            try {
                tenancy()->initialize($tenantTuteladoId);

                $cursoTutelado = CursoTutelado::query()
                    ->whereKey($shared->curso_tutelado_tutelado_id)
                    ->first();

                if ($cursoTutelado) {
                    $cursoTutelado->forceFill([
                        'curso_tutelado_shared_id' => $shared->getKey(),
                        'tipo_tutela' => 'externa',
                        'instituicao_tutora_id' => null,
                    ])->save();
                }
            } finally {
                tenancy()->initialize($tenantTutorId);
            }
        }

        $this->notificacaoService->notificarResultadoTroca(
            $shared,
            $tenantTutorId,
            $status === TutelaStatus::ACTIVO ? 'aprovada' : 'rejeitada',
            'instituicao_nova',
        );

        $notification->markAsRead();

        return [
            'type' => 'success',
            'message' => $status === TutelaStatus::ACTIVO
                ? 'Tutela aprovada com sucesso.'
                : 'Solicitação de tutela rejeitada.',
        ];
    }
}
