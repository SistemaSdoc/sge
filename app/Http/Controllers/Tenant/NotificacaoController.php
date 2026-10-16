<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Notificacao\DecidirTutela as DecidirTutelaAction;
use App\Actions\Tenant\Notificacao\MarkAllNotificationsAsRead;
use App\Actions\Tenant\Notificacao\MarkNotificationAsRead;
use App\Enums\TutelaStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\Tenant\NotificacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly NotificacaoService $notificacaoService,
        private readonly MarkNotificationAsRead $markNotificationAsRead,
        private readonly MarkAllNotificationsAsRead $markAllNotificationsAsRead,
        private readonly DecidirTutelaAction $decidirTutelaAction,
    ) {}

    /**
     * Mostra a lista de notificações do usuário autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user('tenant');

        abort_unless($user instanceof User, 401);

        return Inertia::render('tenant/notificacoes/index', [
            'notificacoes' => Inertia::scroll(fn () => $this->notificacaoService->pagina($user)
                ->through(fn (DatabaseNotification $notificacao) => $this->notificacaoService->formatarNotificacao($notificacao))),
            'naoLidas' => $this->notificacaoService->naoLidas($user),
        ]);
    }

    public function sino(Request $request)
    {
        $user = $request->user('tenant');
        abort_unless($user instanceof User, 401);

        if (! $request->expectsJson()) {
            return redirect()->route('tenant.dashboard.notificacoes.index');
        }

        return response()->json([
            'notificacoes' => $this->notificacaoService->sino($user)
                ->map(fn (DatabaseNotification $notificacao) => $this->notificacaoService->formatarNotificacao($notificacao))
                ->values(),
            'nao_lidas' => $this->notificacaoService->naoLidas($user),
        ]);
    }

    /**
     * Mostra os detalhes de uma notificação específica.
     */
    public function show(Request $request, string $notification): Response
    {
        $user = $request->user('tenant');
        abort_unless($user instanceof User, 401);

        $item = $user->notifications()->findOrFail($notification);

        $item->markAsRead();

        return Inertia::render('tenant/notificacoes/show', [
            'notificacao' => $this->notificacaoService->serializar($item, true),
        ]);
    }

    /**
     * Marca uma notificação do usuário autenticado como lida.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $user = $request->user('tenant');

        abort_unless($user instanceof User, 401);

        $this->markNotificationAsRead->handle($user, $id);

        return Redirect::back();
    }

    /**
     * Marca todas as notificações do usuário autenticado como lidas.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user('tenant');

        abort_unless($user instanceof User, 401);

        $this->markAllNotificationsAsRead->handle($user);

        return Redirect::back();
    }

    /**
     * Aprova a decisão de tutela associada à notificação.
     */
    public function aprovarTutela(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user('tenant');
        abort_unless($user instanceof User, 401);

        $result = $this->decidirTutelaAction->handle(
            $user,
            $notification,
            TutelaStatus::ACTIVO
        );

        return Redirect::route('tenant.dashboard.notificacoes.show', $result['notificationId'])
            ->with('toast', $result['toast']);
    }

    /**
     * Rejeita a decisão de tutela associada à notificação.
     */
    public function rejeitarTutela(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user('tenant');
        abort_unless($user instanceof User, 401);

        $result = $this->decidirTutelaAction->handle(
            $user,
            $notification,
            TutelaStatus::REJEITADO
        );

        return Redirect::route('tenant.dashboard.notificacoes.show', $result['notificationId'])
            ->with('toast', $result['toast']);
    }
}
