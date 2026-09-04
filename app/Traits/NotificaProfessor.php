<?php

namespace App\Traits;

use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\PeriodoLancamentoNotas;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Notifications\Professor\PrazoLancamentoNotasDefinidoNotification;
use App\Notifications\Professor\ProfessorAdicionadoAoCursoNotification;
use App\Notifications\Professor\ProfessorAtribuidoADisciplinaNotification;
use App\Notifications\Professor\ProfessorCriadoNotification;
use Illuminate\Support\Facades\Notification;

trait NotificaProfessor
{
    protected function notificarProfessorCriado(
        User $user,
        string $passwordPlain
    ): void {
        $user->notify(new ProfessorCriadoNotification(
            $user,
            $passwordPlain
        ));
    }

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

    protected function notificarPrazoLancamentoNotas(PeriodoLancamentoNotas $periodo): void
    {
        $professores = Professor::whereHas(
            'turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($q) use ($periodo) {
                $q->where('instituicao_id', $periodo->instituicao_id);
            })
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        Notification::send(
            $professores,
            new PrazoLancamentoNotasDefinidoNotification($periodo)
        );
    }
}
