<?php

namespace App\Actions\Tenant\Notificacao;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Resolve a notificação de tutela pertencente ao usuário.
 */
final class ResolverNotificacaoTutela
{
    /** @var list<string> */
    private const TIPOS = [
        'solicitacao_tutela',
        'troca_tutela',
        'conversao_tutela_propria',
        'conversao_tutela_propria_pendente',
        'troca_tutela_rejeitada',
        'troca_tutela_resultado',
        'conversao_tutela_propria_resultado',
    ];

    /**
     * Procura primeiro pelo ID do vínculo e usa o nome do curso como fallback legado.
     *
     * @throws ModelNotFoundException
     */
    public function handle(User $user, string $sharedId): DatabaseNotification
    {
        $notification = $user->notifications()
            ->whereIn('data->tipo', self::TIPOS)
            ->where('data->curso_tutelado_shared_id', $sharedId)
            ->latest()
            ->first();

        if ($notification instanceof DatabaseNotification) {
            return $notification;
        }

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($sharedId);

        return $user->notifications()
            ->whereIn('data->tipo', self::TIPOS)
            ->where('data->curso_nome', $shared->curso_nome)
            ->latest()
            ->firstOrFail();
    }
}
