<?php

namespace App\Policies;

use App\Models\GrupoPap;
use App\Models\User;

class GrupoPapPolicy
{
    /**
     * Determina se o utilizador pode listar grupos PAP.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('grupopap.viewAny');
    }

    /**
     * Determina se o utilizador pode ver um grupo PAP específico.
     *
     * Staff com permission vê apenas grupos da sua instituição.
     * Aluno vê apenas o seu próprio grupo.
     */
    public function view(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Aluno')) {
            return $grupoPap->alunos()
                ->where('aluno_id', $user->aluno?->id)
                ->exists();
        }

        if ($user->hasRole('Professor')) {
            $professor = $user->professor;

            // ✅ Professor DA TURMA
            $daFturma = $grupoPap->turma
                ->professores()
                ->where('professores.id', $professor?->id)
                ->exists();

            // ✅ Jurado do grupo
            $ehJurado = $grupoPap->jurados()
                ->where('professor_id', $professor?->id)
                ->exists();

            // ✅ Professor TUTOR (criou/tutela o grupo)
            $ehTutor = $grupoPap->professor_tutor_id === $professor?->id;

            return ($daFturma || $ehJurado || $ehTutor)
                && $grupoPap->instituicao()?->id === $user->instituicao_id;
        }

        return $user->hasPermissionTo('grupopap.view')
            && (
                $grupoPap->instituicao()?->id === $user->instituicao_id
                || $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
            );

        return true;
    }

    /**
     * Determina se o utilizador pode criar grupos PAP.
     */
    public function create(User $user): bool
    {
        return $user->can('grupopap.create')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function update(User $user, GrupoPap $grupoPap): bool
    {
        if (!$user->hasRole('Professor')) {
            return $user->hasPermissionTo('grupopap.update')
                && $grupoPap->instituicao()?->id === $user->instituicao_id;
        }

        $professor = $user->professor;

        // ✅ Só professor DA TURMA ou TUTOR (não jurado)
        $daFturma = $grupoPap->turma
            ->professores()
            ->where('professores.id', $professor?->id)
            ->exists();

        $ehTutor = $grupoPap->professor_tutor_id === $professor?->id;

        return ($daFturma || $ehTutor)
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode corrigir o tema PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function corrigirTema(User $user, GrupoPap $grupoPap): bool
    {
        if (!$grupoPap->podeSerEditado()) {
            return false;
        }

        if (!$user->can('grupopap.corrigirTema')) {
            return false;
        }

        $ehTutor = $grupoPap->professor_tutor_id === $user->professor?->id;

        $ehMembro = $grupoPap->elementos()
            ->whereHas('aluno', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        return $ehTutor || $ehMembro;
    }

    /**
     * Determina se o utilizador pode atualizar a nota do grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function aprovar(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('grupopap.aprovar')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id; // ← adicionar
    }

    /**
     * Determina se o utilizador pode reprovar o grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function reprovar(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('grupopap.reprovar')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id; // ← adicionar
    }

    /**
     * Determina se o utilizador pode solicitar melhoria para o grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function solicitarMelhoria(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('grupopap.solicitarMelhoria')
            && $grupoPap->podeSerAprovado()
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id; // ← adicionar
    }
    /**
     * Determina se o utilizador pode definir a data de defesa.
     *
     * Requer permission específica e que o grupo pertença à sua instituição.
     */
    public function definirData(User $user, GrupoPap $grupoPap): bool
    {
        if ($grupoPap->status_aprovacao !== 'aprovado') {
            return false;
        }

        return $user->can('grupopap.definirData')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id; // ← era instituicao()
    }

    /**
     * Determina se o utilizador pode apagar um grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function delete(User $user, GrupoPap $grupoPap): bool
    {
        return $user->can('grupopap.delete')
            && $grupoPap->instituicao()->id === $user->instituicao_id;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function restore(User $user, GrupoPap $grupoPap): bool
    {
        return false;
    }

    /**
     * Exclusivo do SuperAdmin via Gate::before().
     */
    public function forceDelete(User $user, GrupoPap $grupoPap): bool
    {
        return false;
    }
}
