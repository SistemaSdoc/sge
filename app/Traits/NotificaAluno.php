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
    /**
     * Notifica o aluno com as credenciais da conta recém-criada.
     */
    protected function notificarAlunoCriado(
        User $user,
        string $passwordPlain
    ): void {
        $domain = tenant()?->domains?->first()?->domain;
        $loginUrl = $domain
            ? tenant_route($domain, 'tenant.login')
            : route('tenant.login');

        $user->notify(new AlunoCriadoNotification(
            $user,
            $passwordPlain,
            $loginUrl,
        ));
    }

    /**
     * Informa o aluno sobre a transferência para uma nova turma.
     */
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

    /**
     * Envia ao aluno o aviso de propina em atraso.
     */
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

    /**
     * Confirma ao utilizador o registo de um pagamento.
     */
    protected function notificarPagamentoRegistado(
        User $user,
        Pagamento $pagamento
    ): void {
        $user->notify(new PagamentoRegistadoNotification($pagamento));
    }
}
