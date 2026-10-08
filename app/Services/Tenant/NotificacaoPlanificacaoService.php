<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Planificacao;
use App\Models\Tenant\User;
use App\Notifications\Professor\PlanificacaoPublicadaNotificacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificacaoPlanificacaoService
{
    /**
     * Notifica professores e alunos quando uma planificação é criada ou atualizada.
     *
     * @return array{professores: int, alunos: int}
     */
    public function notificarPublicacao(Planificacao $planificacao, int $versao, bool $primeiraVersao = true): array
    {
        $planificacao->loadMissing(['disciplina', 'classe', 'anoLectivo']);

        $professores = $this->professoresInteressados($planificacao);
        $alunos      = $this->alunosInteressados($planificacao);

        $notificacao = new PlanificacaoPublicadaNotificacao(
            $planificacao,
            $versao,
            $primeiraVersao
        );

        // ─── Professores ───
        if ($professores->isNotEmpty()) {
            Notification::send($professores, $notificacao);
        }

        // ─── Alunos (também em batch) ───
        if ($alunos->isNotEmpty()) {
            Notification::send($alunos, $notificacao);
        }

        Log::info('Planificação publicada — notificações enviadas', [
            'planificacao_id' => $planificacao->id,
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
    // AUXILIARES
    // ============================================================

    /**
     * Professores que lecionam esta disciplina nesta classe (ano letivo ativo).
     */
    private function professoresInteressados(Planificacao $planificacao)
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Professor'))
            ->whereHas('professor', function ($q) use ($planificacao) {
                $q->whereExists(function ($sub) use ($planificacao) {
                    $sub->select(DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                        ->where('classe_turno_disciplina.disciplina_id', $planificacao->disciplina_id)
                        ->where('curso_classe.classe_id', $planificacao->classe_id);
                });
            })
            ->where('instituicao_id', $planificacao->instituicao_id)
            ->get();
    }

    /**
     * Alunos que estão atualmente numa turma da classe desta planificação.
     */
    private function alunosInteressados(Planificacao $planificacao)
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Aluno'))
            ->whereHas('aluno', function ($q) use ($planificacao) {
                $q->whereExists(function ($sub) use ($planificacao) {
                    $sub->select(DB::raw(1))
                        ->from('turma_aluno')
                        ->join('turmas', 'turma_aluno.turma_id', '=', 'turmas.id')
                        ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('turma_aluno.aluno_id', 'alunos.id')
                        ->where('turma_aluno.activo', true)
                        ->where('curso_classe.classe_id', $planificacao->classe_id);
                });
            })
            ->where('instituicao_id', $planificacao->instituicao_id)
            ->get();
    }
}