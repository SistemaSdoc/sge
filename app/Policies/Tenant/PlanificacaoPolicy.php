<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Planificacao;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class PlanificacaoPolicy
{
    /**
     * Criar nova planificação — só diretor/subdirector.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'SuperAdmin']);
    }

    /**
     * Atualizar — mesma regra de create.
     */
    public function update(User $user, Planificacao $planificacao): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'SuperAdmin']);
    }

    /**
     * Apagar — mesma regra.
     */
    public function delete(User $user, Planificacao $planificacao): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector', 'SuperAdmin']);
    }

    /**
     * Ver a planificação.
     *
     * - Director/Subdirector/Secretaria/SuperAdmin: tudo
     * - Professor: só das disciplinas que leciona (na classe)
     * - Aluno: só da sua classe atual (turma ativa)
     */
    public function view(User $user, Planificacao $planificacao): bool
    {
        // Gestão vê tudo
        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria', 'SuperAdmin'])) {
            return true;
        }

        // Professor — só se leciona a disciplina nesta classe
        if ($user->hasRole('Professor')) {
            return $this->professorLecionaNaDisciplina(
                $user,
                $planificacao->disciplina_id,
                $planificacao->classe_id
            );
        }

        // Aluno — só da sua classe atual
        if ($user->hasRole('Aluno')) {
            $aluno = $user->aluno;

            if (! $aluno) {
                return false;
            }

            return $this->alunoEstaNaClasse($aluno, $planificacao->classe_id);
        }

        return false;
    }

    /**
     * Ver versões antigas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Director',
            'Subdirector',
            'Secretaria',
            'Professor',
            'Aluno',
            'SuperAdmin',
        ]);
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function professorLecionaNaDisciplina(
        User $user,
        string $disciplinaId,
        string $classeId
    ): bool {
        $professor = $user->professor;

        if (! $professor) {
            return false;
        }

        return DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->where('classe_turno_disciplina.disciplina_id', $disciplinaId)
            ->where('curso_classe.classe_id', $classeId)
            ->exists();
    }

    private function alunoEstaNaClasse($aluno, string $classeId): bool
    {
        return DB::table('turma_aluno')
            ->join('turmas', 'turma_aluno.turma_id', '=', 'turmas.id')
            ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('turma_aluno.aluno_id', $aluno->id)
            ->where('turma_aluno.activo', true)
            ->where('curso_classe.classe_id', $classeId)
            ->exists();
    }
}