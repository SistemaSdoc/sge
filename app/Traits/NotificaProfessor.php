<?php

namespace App\Traits;

use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\PeriodoLancamentoNotas;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SolicitacaoEdicaoPauta;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Notifications\Pauta\DecisaoEdicaoPautaNotification;
use App\Notifications\Pauta\SolicitacaoEdicaoPautaDirectorNotification;
use App\Notifications\Pauta\SolicitacaoEdicaoPautaProfessorNotification;
use App\Notifications\Professor\PrazoLancamentoNotasDefinidoNotification;
use App\Notifications\Professor\ProfessorAdicionadoAoCursoNotification;
use App\Notifications\Professor\ProfessorAtribuidoADisciplinaNotification;
use App\Notifications\Professor\ProfessorCriadoNotification;
use Illuminate\Support\Facades\Notification;

trait NotificaProfessor
{
    /**
     * Notifica o professor com as credenciais da conta recém-criada.
     */
    protected function notificarProfessorCriado(
        User $user,
        string $passwordPlain
    ): void {
        $user->notify(new ProfessorCriadoNotification(
            $user,
            $passwordPlain
        ));
    }

    /**
     * Informa o professor de que foi adicionado a um curso tutelado.
     */
    protected function notificarProfessorAdicionadoAoCurso(
        Professor $professor,
        CursoTutelado $cursoTutelado
    ): void {
        $user = $professor->user;

        if ($user) {
            $user->notify(new ProfessorAdicionadoAoCursoNotification(
                $professor,
                $cursoTutelado
            ));
        }
    }

    /**
     * Informa o professor de que lhe foi atribuída uma disciplina numa turma.
     */
    protected function notificarProfessorAtribuidoADisciplina(
        Professor $professor,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ): void {
        $user = $professor->user;

        if ($user) {
            $user->notify(new ProfessorAtribuidoADisciplinaNotification(
                $professor,
                $turma,
                $classeTurnoDisciplina
            ));
        }
    }

    /**
     * Notifica os professores sobre um prazo de lançamento de notas.
     */
    protected function notificarPrazoLancamentoNotas(PeriodoLancamentoNotas $periodo): void
    {
        $professores = Professor::whereHas(
            'turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
            function ($q) use ($periodo) {
                $q->where('instituicao_id', $periodo->instituicao_id);
            }
        )
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        Notification::send(
            $professores,
            new PrazoLancamentoNotasDefinidoNotification($periodo)
        );
    }

    /**
     * Envia uma solicitação de edição de pauta ao professor e aos diretores.
     */
    protected function notificarSolicitacaoEdicaoPauta(
        SolicitacaoEdicaoPauta $solicitacao
    ): void {
        $solicitacao->professor->notify(
            new SolicitacaoEdicaoPautaProfessorNotification($solicitacao)
        );

        $instituicaoId = $solicitacao->turmaDisciplinaProfessor
            ->turma->cursoClasseTurno->cursoClasse->cursoTutelado->instituicao_tutora_id;

        $directores = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Director', 'Subdirector']))
            ->where('instituicao_id', $instituicaoId)
            ->get();

        foreach ($directores as $director) {
            $director->notify(
                new SolicitacaoEdicaoPautaDirectorNotification($solicitacao, $director)
            );
        }
    }

    /**
     * Informa o professor sobre a decisão relativa à edição da pauta.
     */
    protected function notificarDecisaoEdicaoPauta(
        SolicitacaoEdicaoPauta $solicitacao
    ): void {
        $solicitacao->load([
            'professor',
            'turmaDisciplinaProfessor.turma',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
        ]);

        $solicitacao->professor?->notify(
            new DecisaoEdicaoPautaNotification($solicitacao)
        );
    }
}
