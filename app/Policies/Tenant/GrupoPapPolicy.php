<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class GrupoPapPolicy
{
    /**
     * Determina se o utilizador pode listar grupos PAP.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Secretario do Curso')
            || (! $user->hasRole('Aluno') && $user->can('grupopap.viewAny'));
    }

    /**
     * Determina se o utilizador pode ver um grupo PAP específico.
     *
     * Staff com permission vê apenas grupos da sua instituição.
     * Aluno vê apenas o seu próprio grupo.
     */
    public function view(User $user, GrupoPap $grupo): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            if ($grupo->getAttribute('secretaria_course_access') === true) {
                return $user->can('grupopap.view');
            }

            $cursoTuteladoId = $grupo->turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;

            return $user->can('grupopap.view')
                && $cursoTuteladoId !== null
                && $user->cursosSecretariados()->whereKey($cursoTuteladoId)->exists();
        }

        if ($user->hasAnyRole(['Director', 'Subdirector'])) {
            return true;
        }

        if ($user->hasRole('Aluno')) {
            return $grupo->alunos()
                ->where('aluno_id', $user->aluno?->id)
                ->exists();
        }

        if ($user->hasRole('Professor')) {
            $professor = $user->professor;

            if (! $professor) {
                return false;
            }

            // Tutor
            if ($grupo->professor_tutor_id === $professor->id) {
                return true;
            }

            if ($user->hasAnyRole(['Coordenador do Grupo Disciplinar', 'Membro do Grupo Disciplinar'])) {
                return $user->hasPermissionTo('grupopap.view')
                    && (
                        $grupo->instituicao()?->id === $user->instituicao_id
                        || $grupo->instituicaoTutora()?->id === $user->instituicao_id
                    );
            }

            // Coordenador do curso tutelado ou do grupo disciplinar, mesmo sem a role específica
            if ($user->hasPermissionTo('grupopap.view')) {
                $ehCoordenador = CursoTuteladoProfessor::where('professor_id', $professor->id)
                    ->where('coordenador', true)
                    ->exists();

                if ($ehCoordenador) {
                    return $grupo->instituicao()?->id === $user->instituicao_id
                        || $grupo->instituicaoTutora()?->id === $user->instituicao_id;
                }
            }

            return false;
        }

        return $user->hasPermissionTo('grupopap.view')
            && (
                $grupo->instituicao()?->id === $user->instituicao_id
                || $grupo->instituicaoTutora()?->id === $user->instituicao_id
            );
    }

    /**
     * Determina se o utilizador pode criar grupos PAP.
     */
    public function create(User $user): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.create')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode editar um grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function update(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $user->hasRole('Professor')) {
            return $user->hasPermissionTo('grupopap.update')
                && $grupoPap->instituicao()?->id === $user->instituicao_id;
        }

        $professor = $user->professor;

        $ehTutor = $grupoPap->professor_tutor_id === $professor?->id;

        return $ehTutor
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    /**
     * Determina se o utilizador pode corrigir o tema PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function corrigirTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $grupoPap->podeSerEditado()) {
            return false;
        }

        if (! $user->can('grupopap.corrigirTema')) {
            return false;
        }

        // Apenas membros do grupo podem corrigir o tema
        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function atualizarTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if ($user->hasRole('Aluno')) {
            return $this->corrigirTema($user, $grupoPap);
        }

        return $user->can('grupopap.update')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    public function reenviarTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        if (! $grupoPap->podeSerReenviado()) {
            return false;
        }

        if ($user->hasRole('Aluno')) {
            return $user->can('grupopap.corrigirTema')
                && $grupoPap->elementos()
                    ->whereHas('aluno', fn ($query) => $query->where('user_id', $user->id))
                    ->exists();
        }

        return $user->can('grupopap.update')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
    }

    public function viewHistorico(User $user, GrupoPap $grupoPap): bool
    {
        return $this->view($user, $grupoPap);
    }

    public function viewMelhorias(User $user): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.solicitarMelhoria')
            && $user->instituicao_id !== null;
    }

    /**
     * Determina se o utilizador pode atualizar a nota do grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function aprovar(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.aprovar')
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
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.reprovar')
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
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.solicitarMelhoria')
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
        if ($user->hasRole('Secretario do Curso') || $grupoPap->status_aprovacao !== 'aprovado') {
            return false;
        }

        return $user->can('grupopap.definirData')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id; // ← era instituicao()
    }

    /**
     * Determina se o utilizador pode definir o tema do grupo PAP.
     *
     * Requer permission específica e que o grupo pertença à sua instituição.
     */
    public function definirTema(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso') || ! $grupoPap->podeDefinirTema()) {
            return false;
        }

        if (! $user->can('grupopap.definirTema')) {
            return false;
        }

        // Só membros do grupo
        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Determina se o utilizador pode aprovar o grupo PAP como tutor.
     */
    public function aprovarComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $grupoPap->podeSerAprovadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Determina se o utilizador pode reprovar o grupo PAP como tutor.
     */
    public function solicitarMelhoriaComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $grupoPap->podeSerAprovadoPeloTutor() // status === 'submetido'
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    // ── Trabalho PAP ─────────────────────────────────────────────────────────────

    /**
     * Qualquer integrante do grupo pode submeter o trabalho,
     * desde que o trabalho esteja num estado que aceite submissão.
     */
    public function submeterTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return false;
        }

        return $grupoPap->elementos()
            ->whereHas('aluno', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Só o professor tutor do grupo pode aprovar como tutor.
     */
    public function aprovarTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Só o professor tutor do grupo pode solicitar correção como tutor.
     */
    public function solicitarCorrecaoTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPeloTutor()
            && $grupoPap->professor_tutor_id === $user->professor?->id;
    }

    /**
     * Coordenação da instituição tutora aprova definitivamente.
     */
    public function aprovarTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPelaCoordenacao()
            && $user->can('grupopap.aprovar')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Coordenação da instituição tutora solicita correção.
     */
    public function solicitarCorrecaoTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        if ($user->hasRole('Secretario do Curso')) {
            return false;
        }

        $trabalho = $grupoPap->trabalhoPap;

        return $trabalho?->podeSerAnalisadoPelaCoordenacao()
            && $user->can('grupopap.aprovar')
            && $grupoPap->instituicaoTutora()?->id === $user->instituicao_id;
    }

    /**
     * Download disponível para tutor, coordenação da tutora, e membros do grupo.
     */
    public function downloadVersaoTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        if (! $grupoPap->trabalhoPap) {
            return false;
        }

        // Membros do grupo
        $ehIntegrante = $grupoPap->elementos()
            ->whereHas('aluno', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        if ($ehIntegrante) {
            return true;
        }

        // Tutor do grupo
        if ($grupoPap->professor_tutor_id === $user->professor?->id) {
            return true;
        }

        // Leitores da instituição do grupo ou da instituição tutora
        return $user->can('grupopap.view')
            && (
                $grupoPap->instituicao()?->id === $user->instituicao_id
                || $grupoPap->instituicaoTutora()?->id === $user->instituicao_id
            );
    }

    /**
     * Determina se o utilizador pode apagar um grupo PAP.
     *
     * Requer permission e que o grupo pertença à sua instituição.
     */
    public function delete(User $user, GrupoPap $grupoPap): bool
    {
        return ! $user->hasRole('Secretario do Curso')
            && $user->can('grupopap.delete')
            && $grupoPap->instituicao()?->id === $user->instituicao_id;
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

    public function selecionarInstituicao(User $user): bool
    {
        return $user->can('grupopap.selecionarInstituicao')
            && $user->instituicao_id !== null;
    }

    public function selecionarAnoLectivo(User $user): bool
    {
        return $user->can('grupopap.selecionarAnoLectivo')
            && $user->instituicao_id !== null;
    }

    private function isPapDirector(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Subdirector']);
    }
}
