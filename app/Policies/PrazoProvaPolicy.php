<?php

namespace App\Policies;

use App\Models\PrazoProva;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrazoProvaPolicy
{
    use HandlesAuthorization;

    /**
     * Roles com permissões administrativas.
     */
    private const ADMIN_ROLES = ['Director', 'Subdirector', 'Coordenador'];

    /**
     * Verifica se o utilizador tem role administrativa.
     */
    private function hasAdminRole(User $user): bool
    {
        return $user->roles()->whereIn('name', self::ADMIN_ROLES)->exists();
    }

    /**
     * Verifica se o utilizador pertence à mesma instituição do prazo.
     */
    private function mesmaInstituicao(User $user, PrazoProva $prazo): bool
    {
        // SuperAdmin pode ver tudo (bypass multi-instituição)
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $prazo->instituicao_id === $user->instituicao_id;
    }

    /**
     * Before – Apenas SuperAdmin tem bypass global.
     * Director NÃO tem bypass (tem de estar na mesma instituição).
     */
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return null;
    }

    /**
     * Pode listar prazos (o controller filtra por instituição).
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAdminRole($user);
    }

    /**
     * Pode ver um prazo específico (mesma instituição).
     */
    public function view(User $user, PrazoProva $prazo): bool
    {
        return $this->hasAdminRole($user) && $this->mesmaInstituicao($user, $prazo);
    }

    /**
     * Pode criar prazos (na sua própria instituição).
     */
    public function create(User $user): bool
    {
        return $this->hasAdminRole($user) || $user->can('prazo-prova.create');
    }

    /**
     * Pode atualizar (mesma instituição).
     */
    public function update(User $user, PrazoProva $prazo): bool
    {
        return $this->hasAdminRole($user) && $this->mesmaInstituicao($user, $prazo);
    }

    /**
     * Pode excluir (mesma instituição).
     */
    public function delete(User $user, PrazoProva $prazo): bool
    {
        return $this->hasAdminRole($user) && $this->mesmaInstituicao($user, $prazo);
    }

    /**
     * Prorrogar – mesma lógica de update.
     */
    public function prorrogar(User $user, PrazoProva $prazo): bool
    {
        return $this->update($user, $prazo);
    }

    /**
     * Fechar – mesma lógica de update.
     */
    public function fechar(User $user, PrazoProva $prazo): bool
    {
        return $this->update($user, $prazo);
    }
}