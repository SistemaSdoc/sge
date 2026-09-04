<?php

namespace App\Traits;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Notifications\Aluno\AlunoCriadoNotification;
use App\Notifications\Aluno\AlunoTransferidoTurmaNotification;
use App\Notifications\Aluno\PagamentoRegistadoNotification;
use App\Notifications\Aluno\PropinaEmAtrasoNotification;

trait NotificaAluno
{
    protected function notificarAlunoCriado(
        User $user,
        string $passwordPlain
    ): void {
        $user->notify(new AlunoCriadoNotification(
            $user,
            $passwordPlain
        ));
    }

    protected function notificarAlunoTransferidoTurma(
        Aluno $aluno,
        Turma $turma
    ): void {
        $user = $aluno->user;

        if ($user) {
            $user->notify(new AlunoTransferidoTurmaNotification(
                $aluno,
                $turma
            ));
        }
    }

    protected function notificarPropinaEmAtraso(
        User $user,
        int $totalMeses,
        float $valorTotal,
        array $meses,
        string $assinatura
    ): void {
        $user->notify(new PropinaEmAtrasoNotification(
            $totalMeses,
            $valorTotal,
            $meses,
            $assinatura
        ));
    }

    protected function notificarPagamentoRegistado(
        User $user,
        Pagamento $pagamento
    ): void {
        $user->notify(new PagamentoRegistadoNotification($pagamento));
    }
}
