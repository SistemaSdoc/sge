<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Notificacao\ResolverNotificacaoTutela;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RedirectLegacyTutelaNotificationController extends Controller
{
    public function __construct(
        private readonly ResolverNotificacaoTutela $resolverNotificacaoTutela,
    ) {}

    /**
     * Redirecciona um link antigo com shared ID para a rota canónica por notification ID.
     */
    public function __invoke(Request $request, string $shared): RedirectResponse
    {
        $user = $request->user('tenant');
        abort_unless($user instanceof User, 401);

        $notification = $this->resolverNotificacaoTutela->handle($user, $shared);

        return Redirect::route('tenant.dashboard.notificacoes.show', $notification->getKey());
    }
}
