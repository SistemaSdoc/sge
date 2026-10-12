<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Planificacao;
use App\Models\Tenant\User;
use App\Notifications\Professor\PlanificacaoPublicadaNotificacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificacaoPlanificacaoService
{
    /**
     * Notifica professores e alunos que PODEM ver a planificação.
     * Aplica exatamente as mesmas restrições do PlanificacaoController.
     */
    public function notificarPublicacao(
        Planificacao $planificacao,
        int $versao,
        bool $primeiraVersao = true
    ): array {
        $planificacao->loadMissing(['disciplina', 'classe', 'anoLectivo', 'curso']);

        // Segurança: sem curso_id não conseguimos filtrar corretamente
        if (! $planificacao->curso_id) {
            Log::warning('Planificação sem curso_id — não notifica ninguém', [
                'planificacao_id' => $planificacao->id,
            ]);

            return ['professores' => 0, 'alunos' => 0];
        }

        $professores = $this->professoresInteressados($planificacao);
        $alunos      = $this->alunosInteressados($planificacao);

        $notificacao = new PlanificacaoPublicadaNotificacao(
            $planificacao,
            $versao,
            $primeiraVersao
        );

        if ($professores->isNotEmpty()) {
            Notification::send($professores, $notificacao);
        }

        if ($alunos->isNotEmpty()) {
            Notification::send($alunos, $notificacao);
        }

        Log::info('Planificação publicada — notificações enviadas', [
            'planificacao_id' => $planificacao->id,
            'curso_id'        => $planificacao->curso_id,
            'disciplina_id'   => $planificacao->disciplina_id,
            'classe_id'       => $planificacao->classe_id,
            'versao'          => $versao,
            'professores'     => $professores->count(),
            'alunos'          => $alunos->count(),
        ]);

        return [
            'professores' => $professores->count(),
            'alunos'      => $alunos->count(),
        ];
    }

    // ============================================================
    // PROFESSORES — que lecionam ESTA disciplina NESTE curso/classe
    // ============================================================

    /**
     * Professores atribuídos (via turma_disciplina_professor) à disciplina
     * da planificação, no MESMO curso e classe.
     */
    private function professoresInteressados(Planificacao $planificacao)
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Professor'))
            ->whereHas('professor', function ($q) use ($planificacao) {
                $q->whereExists(function ($sub) use ($planificacao) {
                    $sub->select(\DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                        // disciplina exata
                        ->where('classe_turno_disciplina.disciplina_id', $planificacao->disciplina_id)
                        // classe exata
                        ->where('curso_classe.classe_id', $planificacao->classe_id)
                        // curso exato (novo)
                        ->where('curso_classe.curso_tutelado_id', $planificacao->curso_id);
                });
            })
            ->where('instituicao_id', $planificacao->instituicao_id)
            ->get();
    }

    // ============================================================
    // ALUNOS — que têm a disciplina na grelha do SEU curso/classe
    // ============================================================

    /**
     * Alunos ativos numa turma cujo curso, classe e grelha correspondem
     * ao curso, classe e disciplina da planificação.
     */
    private function alunosInteressados(Planificacao $planificacao)
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Aluno'))
            ->whereHas('aluno', function ($q) use ($planificacao) {
                $q->whereExists(function ($sub) use ($planificacao) {
                    $sub->select(\DB::raw(1))
                        ->from('turma_aluno')
                        ->join('turmas', 'turma_aluno.turma_id', '=', 'turmas.id')
                        ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->join('classe_turno_disciplina', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->whereColumn('turma_aluno.aluno_id', 'alunos.id')
                        // aluno ativo na turma
                        ->where('turma_aluno.activo', true)
                        // mesma classe
                        ->where('curso_classe.classe_id', $planificacao->classe_id)
                        // mesma disciplina na grelha
                        ->where('classe_turno_disciplina.disciplina_id', $planificacao->disciplina_id)
                        // mesmo curso
                        ->where('curso_classe.curso_tutelado_id', $planificacao->curso_id);
                });
            })
            ->where('instituicao_id', $planificacao->instituicao_id)
            ->get();
    }
}