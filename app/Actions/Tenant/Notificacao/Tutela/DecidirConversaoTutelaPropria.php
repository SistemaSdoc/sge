<?php

namespace App\Actions\Tenant\Notificacao\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Services\Tenant\Tutela\TutelaNotificationService;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Decide a conversão de um curso para tutela própria.
 */
final class DecidirConversaoTutelaPropria
{
    /**
     * Recebe os serviços necessários à conversão e às notificações do resultado.
     */
    public function __construct(
        private readonly TutelaService $tutelaService,
        private readonly TutelaNotificationService $notificacaoService,
    ) {}

    /**
     * Decide a conversão iniciada pelo tenant tutor anterior.
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
        abort_if($shared->status !== TutelaStatus::ACTIVO, 422, 'Esta conversão já foi decidida.');

        $resultado = $status === TutelaStatus::ACTIVO ? 'aprovada' : 'rejeitada';
        $notification->data = array_merge($notification->data, ['status' => $resultado]);
        $notification->save();

        if ($status === TutelaStatus::ACTIVO) {
            $tenantTutelado = Tenant::query()->findOrFail($shared->tenant_tutelado_id);
            $tenantTuteladoId = (string) $tenantTutelado->getTenantKey();

            CursoTuteladoShared::on($centralConnection)
                ->whereKey($shared->getKey())
                ->update(['status' => TutelaStatus::ENCERRADO]);

            try {
                tenancy()->initialize($tenantTuteladoId);
                $cursoTutelado = CursoTutelado::query()->findOrFail($shared->curso_tutelado_tutelado_id);
                $this->tutelaService->converterParaTutelaPropria(
                    $cursoTutelado,
                    (string) $tenantTutelado->instituicao_id,
                );
            } finally {
                tenancy()->initialize($tenantActualId);
            }
        }

        $this->notificacaoService->notificarResultadoConversaoTutelaPropria(
            $shared,
            (string) $tenantTutorAnteriorId,
            $resultado,
        );

        $notification->markAsRead();

        return [
            'type' => $status === TutelaStatus::ACTIVO ? 'success' : 'warning',
            'message' => $status === TutelaStatus::ACTIVO
                ? 'Conversão para tutela própria aprovada.'
                : 'Conversão para tutela própria rejeitada.',
        ];
    }
}
