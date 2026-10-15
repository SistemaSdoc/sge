<?php

use App\Models\Central\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;
use App\Notifications\CursoTutelado\SugestaoTemaPapNotification;
use App\Traits\NotificaSugestaoTemaPap;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $routes = new RouteCollection;
    $route = new Route(
        ['GET'],
        '/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}',
        fn () => null,
    );
    $route->name('tenant.dashboard.instituicoes.cursos-tutelados.show');
    $routes->add($route);

    app()->instance('url', new UrlGenerator($routes, Request::create('http://localhost')));
});

function criarSugestaoTemaParaNotificacao(array $attributes = []): SugestaoTemaPap
{
    $instituicaoCurso = new InstituicaoCurso;
    $instituicaoCurso->setAttribute('instituicao_id', 'instituicao-teste');
    $instituicaoCurso->setRelation('curso', new Curso(['nome' => 'Curso de teste']));

    $cursoTutelado = new CursoTutelado;
    $cursoTutelado->setAttribute('id', 'curso-teste');
    $cursoTutelado->setRelation('instituicaoCurso', $instituicaoCurso);

    $sugestao = new SugestaoTemaPap([
        'titulo' => 'Tema de teste',
        ...$attributes,
    ]);
    $sugestao->setAttribute('id', 'sugestao-teste');
    $sugestao->setAttribute('curso_tutelado_id', 'curso-teste');
    $sugestao->setRelation('cursoTutelado', $cursoTutelado);

    return $sugestao;
}

it('formats theme suggestion decisions for database and email notifications', function () {
    $aprovador = new User(['nome' => 'Ana Costa']);
    $user = new User;
    $notification = new SugestaoTemaPapNotification(
        criarSugestaoTemaParaNotificacao([
            'estado_aprovacao' => 'melhoria_solicitada',
            'observacao_aprovacao' => 'Indicar melhor a metodologia.',
        ])->setRelation('aprovadoPor', $aprovador),
        SugestaoTemaPapNotification::MELHORIA_SOLICITADA,
    );

    expect($notification->via($user))->toBe(['database', 'mail'])
        ->and($notification->toArray($user))
        ->toMatchArray([
            'tipo' => 'sugestao_tema_melhoria_solicitada',
            'titulo' => 'Melhoria solicitada para tema PAP',
            'observacao' => 'Indicar melhor a metodologia.',
            'aprovado_por' => 'Ana Costa',
            'sugestao_tema_id' => 'sugestao-teste',
        ])
        ->and($notification->toMail($user)->viewData)
        ->toMatchArray([
            'tituloSugestao' => 'Tema de teste',
            'observacao' => 'Indicar melhor a metodologia.',
            'aprovador' => 'Ana Costa',
        ]);
});

it('includes the approver in the approval notification message', function () {
    $aprovador = new User(['nome' => 'Bruno Silva']);
    $sugestao = criarSugestaoTemaParaNotificacao([
        'estado_aprovacao' => 'aprovada',
    ])->setRelation('aprovadoPor', $aprovador);
    $notification = new SugestaoTemaPapNotification($sugestao, SugestaoTemaPapNotification::APROVADA);

    expect($notification->toArray(new User))
        ->toMatchArray([
            'tipo' => 'sugestao_tema_aprovada',
            'titulo' => 'Sugestão de tema aprovada',
            'aprovado_por' => 'Bruno Silva',
            'mensagem' => 'A sugestão "Tema de teste" do curso "Curso de teste" foi aprovada por Bruno Silva.',
        ]);
});

it('does not label a pending suggestion as approved by coordination', function () {
    $sugestao = criarSugestaoTemaParaNotificacao([
        'estado_aprovacao' => 'pendente',
    ]);
    $notification = new SugestaoTemaPapNotification($sugestao, SugestaoTemaPapNotification::CRIADA);

    expect($notification->toArray(new User))
        ->toMatchArray([
            'tipo' => 'sugestao_tema_criada',
            'aprovado_por' => null,
            'mensagem' => 'Foi registada uma nova sugestão de tema para o curso "Curso de teste".',
        ]);
});

it('allows database and email channels to be dispatched separately', function () {
    $sugestao = criarSugestaoTemaParaNotificacao();
    $databaseNotification = new SugestaoTemaPapNotification($sugestao, 'criada', ['database']);
    $mailNotification = new SugestaoTemaPapNotification($sugestao, 'criada', ['mail']);
    $user = new User;

    expect($databaseNotification->via($user))->toBe(['database'])
        ->and($mailNotification->via($user))->toBe(['mail']);
});

it('sends the database notification immediately and queues email separately', function () {
    Notification::fake();

    $user = new User;
    $user->setAttribute('id', 'destinatario-teste');
    $notifier = new class
    {
        use NotificaSugestaoTemaPap;

        public function enviar(Collection $destinatarios, SugestaoTemaPap $sugestao): void
        {
            $this->enviarNotificacaoSugestaoTema(
                $destinatarios,
                $sugestao,
                SugestaoTemaPapNotification::CRIADA,
                null,
            );
        }
    };

    $notifier->enviar(collect([$user]), criarSugestaoTemaParaNotificacao());

    Notification::assertSentTo(
        $user,
        SugestaoTemaPapNotification::class,
        fn (SugestaoTemaPapNotification $notification, array $channels): bool => $channels === ['database'],
    );
    Notification::assertSentTo(
        $user,
        SugestaoTemaPapNotification::class,
        fn (SugestaoTemaPapNotification $notification, array $channels): bool => $channels === ['mail'],
    );
});

it('includes the suggestion creator among disciplinary decision recipients', function () {
    $creator = new User;
    $creator->setAttribute('id', 'criador-teste');
    $institutionApprover = new User;
    $institutionApprover->setAttribute('id', 'director-teste');
    $groupMember = new User;
    $groupMember->setAttribute('id', 'membro-teste');
    $suggestion = criarSugestaoTemaParaNotificacao()->setRelation('criadoPor', $creator);
    $notifier = new class
    {
        use NotificaSugestaoTemaPap;

        public function adicionarCriador(Collection $recipients, SugestaoTemaPap $suggestion): Collection
        {
            return $this->adicionarCriadorAosDestinatarios($recipients, $suggestion);
        }
    };

    $recipients = $notifier->adicionarCriador(collect([$institutionApprover, $groupMember]), $suggestion);

    expect($recipients->map(fn (User $user) => $user->getKey())->all())
        ->toBe(['director-teste', 'membro-teste', 'criador-teste']);
});
