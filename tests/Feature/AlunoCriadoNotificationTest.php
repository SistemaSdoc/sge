<?php

use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Notifications\Aluno\AlunoCriadoNotification;
use App\Traits\NotificaAluno;
use Illuminate\Support\Facades\Notification;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Models\Domain;

it('uses the active tenant domain for the student login link', function () {
    Notification::fake();

    $domain = new Domain;
    $domain->setAttribute('domain', 'colegio.sge.test');

    $tenant = new Tenant;
    $tenant->setRelation('domains', collect([$domain]));
    app()->instance(TenantContract::class, $tenant);

    $instituicao = new Instituicao([
        'nome' => 'Colégio de Teste',
        'tipo' => 'colegio',
    ]);

    $user = new User([
        'nome' => 'Aluno Teste',
        'email' => 'aluno@example.test',
    ]);
    $user->setRelation('instituicao', $instituicao);

    $notificador = new class
    {
        use NotificaAluno;

        public function enviar(User $user): void
        {
            $this->notificarAlunoCriado($user, '12345678');
        }
    };

    $notificador->enviar($user);

    Notification::assertSentTo(
        $user,
        AlunoCriadoNotification::class,
        fn (AlunoCriadoNotification $notification): bool => parse_url(
            $notification->toMail($user)->viewData['url'],
            PHP_URL_HOST,
        ) === 'colegio.sge.test',
    );
});
