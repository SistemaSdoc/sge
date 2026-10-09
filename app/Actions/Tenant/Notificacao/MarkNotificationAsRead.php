<?php

namespace App\Actions\Tenant\Notificacao;

use App\Models\Tenant\User;

/**
 * Marca como lida uma notificação pertencente ao utilizador.
 */
final class MarkNotificationAsRead
{
    /**
     * Marca como lida a notificação indicada, garantindo que pertence ao utilizador.
     *
     * @param  User  $user  Utilizador autenticado no tenant.
     * @param  string  $notificationId  Identificador da notificação.
     */
    public function handle(User $user, string $notificationId): void
    {
        $user->notifications()->findOrFail($notificationId)->markAsRead();
    }
}
