<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Aula;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AulaPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->hasRole('SuperAdmin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Professor', 'Aluno', 'Director', 'Subdirector',
            'Secretaria', 'Coordenador', 'Coordenador do Grupo Disciplinar',
        ]);
    }

    public function view(User $user, Aula $aula): bool
    {
        if ($user->hasRole('Aluno')) {
            return $aula->estado === 'publicado'
                && $this->alunoPertenceTurma($user, $aula->turma_id);
        }

        if ($user->hasRole('Professor')) {
            $profId = $user->professor?->id;

            if ($profId === $aula->professor_id || $profId === $aula->professor_original_id) {
                return true;
            }

            if (! $user->hasAnyRole([
                'Director', 'Subdirector', 'Coordenador',
                'Coordenador do Grupo Disciplinar',
            ])) {
                return false;
            }
        }

        return $user->hasAnyRole([
            'Director', 'Subdirector', 'Secretaria',
            'Coordenador', 'Coordenador do Grupo Disciplinar',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Professor') && $user->professor()->exists();
    }

    /**
     * Editar conteúdo (título, sumário, TPC) — dentro da janela de 48h.
     */
    public function update(User $user, Aula $aula): bool
    {
        return $this->donoDaAula($user, $aula) && $aula->esta_editavel;
    }

    /**
     * Publicar — só o dono, só se estiver em rascunho.
     * Independente da janela (rascunhos são sempre editáveis).
     */
    public function publicar(User $user, Aula $aula): bool
    {
        return $this->donoDaAula($user, $aula) && $aula->estado === 'rascunho';
    }

    /**
     * Cancelar — só o dono, só se ainda não estiver cancelada.
     * Independente da janela de edição (mudar de estado não é "editar").
     */
    public function cancelar(User $user, Aula $aula): bool
    {
        return $this->donoDaAula($user, $aula) && $aula->estado !== 'cancelado';
    }

    /**
     * Apagar — dono, só se estiver em rascunho (soft delete).
     */
    public function delete(User $user, Aula $aula): bool
    {
        if ($user->hasRole('Director')) {
            return true;
        }

        if ($user->hasRole('Professor')) {
            return $aula->estado === 'rascunho'
                && $user->professor?->id === $aula->professor_id;
        }

        return false;
    }

    public function restore(User $user, Aula $aula): bool
    {
        return $user->hasRole('Director');
    }

    public function forceDelete(User $user, Aula $aula): bool
    {
        return $user->hasRole('Director');
    }

    // ─── Helpers ─────────────────────────────────────────

    /**
     * É o professor dono da aula?
     */
    private function donoDaAula(User $user, Aula $aula): bool
    {
        if (! $user->hasRole('Professor')) {
            return false;
        }

        $profId = $user->professor?->id;

        return $profId !== null
            && ($profId === $aula->professor_id
                || $profId === $aula->professor_original_id);
    }

    private function alunoPertenceTurma(User $user, ?string $turmaId): bool
    {
        if (! $turmaId) {
            return false;
        }

        $aluno = $user->aluno;

        return $aluno
            && $aluno->turmas()->where('turmas.id', $turmaId)->exists();
    }
}