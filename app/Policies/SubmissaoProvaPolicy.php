<?php

namespace App\Policies;

use App\Models\PrazoProva;
use App\Models\SubmissaoProva;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class SubmissaoProvaPolicy
{
    use HandlesAuthorization;

    private const ROLE_DIRECTOR  = 'Director';
    private const ROLE_PROFESSOR = 'Professor';

    /**
     * Verifica se o utilizador pertence à mesma instituição do prazo.
     */
    private function mesmaInstituicaoDoPrazo(User $user, SubmissaoProva $submissao): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        // Compara instituicao do prazo com a do utilizador
        $instituicaoDoPrazo = $submissao->prazo?->instituicao_id;
        return $instituicaoDoPrazo === $user->instituicao_id;
    }

    /**
     * Before – Apenas SuperAdmin tem bypass global.
     */
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }
        return null;
    }

    /**
     * Pode listar submissões (o controller filtra por instituição).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(self::ROLE_DIRECTOR)
            || $user->hasRole(self::ROLE_PROFESSOR);
    }

    /**
     * Pode ver uma submissão específica.
     * - Director: apenas na mesma instituição
     * - Professor: apenas as suas
     */
    public function view(User $user, SubmissaoProva $submissao): bool
    {
        if ($user->hasRole(self::ROLE_DIRECTOR)) {
            return $this->mesmaInstituicaoDoPrazo($user, $submissao);
        }

        if ($user->hasRole(self::ROLE_PROFESSOR)) {
            return $submissao->professor?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Pode criar submissões (apenas Professor).
     */
    public function create(User $user): bool
    {
        return $user->hasRole(self::ROLE_PROFESSOR);
    }

    /**
     * Verifica se o professor pode submeter para um prazo específico.
     */
    public function submeterParaPrazo(User $user, PrazoProva $prazo): bool
    {
        if (!$user->hasRole(self::ROLE_PROFESSOR)) {
            return false;
        }

        // 🔥 Mesma instituição
        if (!$user->hasRole('SuperAdmin') && $prazo->instituicao_id !== $user->instituicao_id) {
            return false;
        }

        // Prazo geral → todos os professores da instituição
        if (is_null($prazo->disciplina_id)) {
            return true;
        }

        $professor = $user->professor;
        if (!$professor) {
            return false;
        }

        return DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->exists();
    }

    /**
     * Pode atualizar (substituir) a submissão.
     */
    public function update(User $user, SubmissaoProva $submissao): bool
    {
        if (!$user->hasRole(self::ROLE_PROFESSOR)) {
            return false;
        }

        $isOwner = $submissao->professor?->user_id === $user->id;
        $prazoAberto = $submissao->prazo?->isAberto();
        $permiteReenvio = $submissao->prazo?->permite_reenvio;

        return $isOwner && $prazoAberto && $permiteReenvio;
    }

    /**
     * Pode excluir.
     * - Director: apenas na mesma instituição
     * - Professor: apenas se for dono, pendente e prazo aberto
     */
    public function delete(User $user, SubmissaoProva $submissao): bool
    {
        if ($user->hasRole(self::ROLE_DIRECTOR)) {
            return $this->mesmaInstituicaoDoPrazo($user, $submissao);
        }

        if ($user->hasRole(self::ROLE_PROFESSOR)) {
            return $submissao->professor?->user_id === $user->id
                && $submissao->estado === 'pendente'
                && $submissao->prazo?->isAberto();
        }

        return false;
    }

    /**
     * Pode avaliar (aprovar/rejeitar).
     */
    public function avaliar(User $user, SubmissaoProva $submissao): bool
    {
        if ($user->hasRole(self::ROLE_DIRECTOR)) {
            return $this->mesmaInstituicaoDoPrazo($user, $submissao);
        }

        return $user->can('avaliar submissao')
            && $this->mesmaInstituicaoDoPrazo($user, $submissao);
    }

    /**
     * Pode visualizar os arquivos.
     */
    public function visualizarArquivo(User $user, SubmissaoProva $submissao): bool
    {
        if ($user->hasRole(self::ROLE_DIRECTOR)) {
            return $this->mesmaInstituicaoDoPrazo($user, $submissao);
        }

        if ($user->hasRole(self::ROLE_PROFESSOR)) {
            return $submissao->professor?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Restaurar (soft delete).
     */
    public function restore(User $user, SubmissaoProva $submissao): bool
    {
        return $user->hasRole(self::ROLE_DIRECTOR)
            && $this->mesmaInstituicaoDoPrazo($user, $submissao);
    }

    /**
     * Forçar exclusão.
     */
    public function forceDelete(User $user, SubmissaoProva $submissao): bool
    {
        return $user->hasRole(self::ROLE_DIRECTOR)
            && $this->mesmaInstituicaoDoPrazo($user, $submissao);
    }
}