<?php

namespace App\Actions\Tenant\Notificacao;

use App\Models\Tenant\User;

/**
 * Marca como lidas todas as notificações não lidas do usuário.
 */
final class MarkAllNotificationsAsRead
{
    /**
     * Marca como lidas todas as notificações não lidas do usuário.
     */
    public function handle(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }
}
