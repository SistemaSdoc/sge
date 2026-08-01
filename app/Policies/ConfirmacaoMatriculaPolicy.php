<?php

namespace App\Policies;

use App\Models\TurmaAluno;
use App\Models\User;

class ConfirmacaoMatriculaPolicy
{
    /**
     * Determina se o utilizador pode listar confirmações de matrícula.
     *
     * Secretaria, Director e Subdirector podem listar
     * as confirmações da sua instituição.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('confirmacoes.viewAny') && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode confirmar a matrícula de um aluno específico.
     *
     * Ação por item (botão/dropdown na listagem): requer permission
     * e que o aluno da confirmação pertença à instituição do utilizador.
     */
    /**
     * Determina se o utilizador pode confirmar a matrícula de um aluno específico.
     *
     * Ação por item (botão/dropdown na listagem): requer permission
     * e que o aluno da turma pertença à instituição do utilizador.
     */
    public function confirmar(User $user, TurmaAluno $turmaAluno): bool
    {
        $instituicaoId = $turmaAluno->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso
            ?->instituicao_id;

        return $user->can('confirmacoes.confirmar') && $instituicaoId === $user->instituicao_id;
    }
}
