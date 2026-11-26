<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\ReclamacaoNota;
use App\Models\Tenant\User;

class ReclamacaoNotaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Aluno',
            'Professor',
            'Membro do Grupo Disciplinar',
            'Coordenador do Grupo Disciplinar',
            'Director',
            'Subdirector',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Aluno')
            && $user->aluno !== null;
    }

    public function view(User $user, ReclamacaoNota $reclamacao): bool
    {
        $reclamacao->loadMissing([
            'nota.turmaAluno.aluno.user',
            'nota.turmaDisciplinaProfessor.professor.user',
        ]);

        if ($reclamacao->solicitado_por === $user->id) {
            return true;
        }

        if ($user->hasAnyRole(['Director', 'Subdirector'])) {
            return $reclamacao->nota
                ?->turmaDisciplinaProfessor
                ?->professor
                ?->user
                ?->instituicao_id === $user->instituicao_id;
        }

        $professorId = $user->professor?->id;

        if ($professorId && $reclamacao->nota
            ?->turmaDisciplinaProfessor
            ?->professor_id === $professorId) {
            return true;
        }

        if (! $professorId) {
            return false;
        }

        $reclamacao->loadMissing('nota.turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado');
        $cursoTuteladoId = $reclamacao->nota
            ?->turmaDisciplinaProfessor
            ?->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->id;

        if ($cursoTuteladoId === null) {
            return false;
        }

        $pertenceAoGrupo = CursoTuteladoProfessor::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->where('professor_id', $professorId)
            ->whereIn('grupo_disciplinar', ['membro', 'coordenador'])
            ->exists();

        if (! $pertenceAoGrupo) {
            return false;
        }

        return $user->hasRole('Professor')
            || $user->hasAnyRole([
                'Membro do Grupo Disciplinar',
                'Coordenador do Grupo Disciplinar',
            ]);

        return false;
    }

    public function update(User $user, ReclamacaoNota $reclamacao): bool
    {
        if (! $reclamacao->estaAberta()) {
            return false;
        }

        $reclamacao->loadMissing([
            'nota.turmaDisciplinaProfessor',
            'nota.turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ]);

        $professorId = $user->professor?->id;
        if (! $professorId) {
            return false;
        }

        $tdp = $reclamacao->nota?->turmaDisciplinaProfessor;
        if ($tdp?->professor_id === $professorId) {
            return true;
        }

        $cursoTuteladoId = $tdp
            ?->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->id;

        if ($cursoTuteladoId === null) {
            return false;
        }

        $pertenceAoGrupo = CursoTuteladoProfessor::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->where('professor_id', $professorId)
            ->whereIn('grupo_disciplinar', ['membro', 'coordenador'])
            ->exists();

        if (! $pertenceAoGrupo) {
            return false;
        }

        return $user->hasRole('Professor')
            || $user->hasAnyRole([
                'Membro do Grupo Disciplinar',
                'Coordenador do Grupo Disciplinar',
            ]);
    }

    public function download(User $user, ReclamacaoNota $reclamacao): bool
    {
        return $this->view($user, $reclamacao);
    }
}
