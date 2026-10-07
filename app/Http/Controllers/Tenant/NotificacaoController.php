<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\TutelaStatus;
use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\User;
use App\Services\Tenant\NotificacaoService;
use App\Services\Tenant\Tutela\TutelaNotificationService;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly NotificacaoService $notificacaoService,
    ) {}

    // ============================================================
    // LISTAGEM / SINO
    // ============================================================

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render('tenant/notificacoes/index', [
            'notificacoes' => Inertia::scroll(fn () => $this->notificacaoService->pagina($user)
                ->through(fn (DatabaseNotification $notificacao) => $this->notificacaoService->formatarNotificacao($notificacao))),
            'naoLidas' => $this->notificacaoService->naoLidas($user),
        ]);
    }

    public function sino(Request $request)
    {
        $user = $request->user();
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

    // ============================================================
    // DETALHE
    // ============================================================

    public function show(Request $request, string $notification)
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        return $this->renderShow($item);
    }

    public function showTutela(Request $request, string $shared)
    {
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $sharedModel = CursoTuteladoShared::on($centralConnection)->find($shared);

        $item = $request->user()->notifications()
            ->get()
            ->first(function ($notification) use ($shared, $sharedModel): bool {
                $tipos = [
                    'solicitacao_tutela',
                    'troca_tutela',
                    'conversao_tutela_propria',
                    'conversao_tutela_propria_pendente',
                    'troca_tutela_rejeitada',
                    'troca_tutela_resultado',
                    'conversao_tutela_propria_resultado',
                ];

                if (! in_array($notification->data['tipo'] ?? null, $tipos, true)) {
                    return false;
                }

                if (($notification->data['curso_tutelado_shared_id'] ?? null) === $shared) {
                    return true;
                }

                return $sharedModel
                    && ($notification->data['curso_nome'] ?? null) === $sharedModel->curso_nome;
            });

        abort_unless($item, 404);

        return $this->renderShow($item);
    }

    private function renderShow(object $item)
    {
        $item->markAsRead();

        return Inertia::render('tenant/notificacoes/show', [
            'notificacao' => $this->notificacaoService->serializar($item, true),
        ]);
    }

    // ============================================================
    // MARCAR COMO LIDA
    // ============================================================

    public function marcarLida(Request $request, string $id)
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();

        Log::debug('NotificacaoController@marcarLida', ['notificacao_id' => $id]);

        return Redirect::back();
    }

    public function marcarTodasLidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        Log::debug('NotificacaoController@marcarTodasLidas', [
            'user_id' => $request->user()->id,
        ]);

        return Redirect::back();
    }

    // ============================================================
    // DIAGNÓSTICO
    // ============================================================

    public function diagnostico(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'user_class' => get_class($user),
            'tem_notifiable' => in_array(
                Notifiable::class,
                class_uses_recursive($user)
            ),
            'tem_metodo_notif' => method_exists($user, 'notifications'),
            'tem_relacao_aluno' => method_exists($user, 'aluno'),
            'tabela_notifications' => Schema::hasTable('notifications'),
            'total_notificacoes' => method_exists($user, 'notifications')
                ? $user->notifications()->count()
                : 'N/A',
            'nao_lidas' => method_exists($user, 'unreadNotifications')
                ? $user->unreadNotifications()->count()
                : 'N/A',
        ]);
    }

    // ============================================================
    // TUTELA — DECISÕES
    // ============================================================

    public function aprovarTutela(Request $request, string $notification)
    {
        return $this->decidirTutela($request, $notification, TutelaStatus::ACTIVO);
    }

    public function rejeitarTutela(Request $request, string $notification)
    {
        return $this->decidirTutela($request, $notification, TutelaStatus::REJEITADO);
    }

    private function decidirTutela(Request $request, string $notification, TutelaStatus $status)
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $tipo = $item->data['tipo'] ?? null;

        abort_unless(in_array($tipo, [
            'solicitacao_tutela',
            'troca_tutela',
            'conversao_tutela_propria',
        ], true), 404);

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $sharedId = $item->data['curso_tutelado_shared_id'] ?? null;

        if (! $sharedId) {
            $sharedId = $this->notificacaoService->resolverSharedIdDaNotificacao($item, $centralConnection);
        }

        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($sharedId);

        // -------- Conversão para tutela própria --------
        if ($tipo === 'conversao_tutela_propria') {
            $tenantTutorAnteriorId = $item->data['tenant_tutor_anterior_id'] ?? null;
            abort_unless((string) $tenantTutorAnteriorId === (string) tenancy()->tenant->getTenantKey(), 403);
            abort_if($shared->status !== TutelaStatus::ACTIVO, 422, 'Esta conversão já foi decidida.');

            $item->data = array_merge($item->data, [
                'status' => $status === TutelaStatus::ACTIVO ? 'aprovada' : 'rejeitada',
            ]);
            $item->save();

            if ($status === TutelaStatus::ACTIVO) {
                $tenantActualId = (string) tenancy()->tenant->getTenantKey();
                $tenantTutelado = Tenant::query()->findOrFail($shared->tenant_tutelado_id);
                $tenantTuteladoId = (string) $tenantTutelado->getTenantKey();

                CursoTuteladoShared::on($centralConnection)
                    ->whereKey($shared->getKey())
                    ->update(['status' => TutelaStatus::ENCERRADO]);

                tenancy()->initialize($tenantTuteladoId);
                $cursoTutelado = CursoTutelado::query()->findOrFail($shared->curso_tutelado_tutelado_id);
                app(TutelaService::class)->converterParaTutelaPropria(
                    $cursoTutelado,
                    (string) $tenantTutelado->instituicao_id,
                );
                tenancy()->initialize($tenantActualId);
            }

            app(TutelaNotificationService::class)->notificarResultadoConversaoTutelaPropria(
                $shared,
                (string) $tenantTutorAnteriorId,
                $status === TutelaStatus::ACTIVO ? 'aprovada' : 'rejeitada',
            );

            $item->markAsRead();

            return Redirect::route('tenant.dashboard.notificacoes.show', $item->id)
                ->with('toast', [
                    'type' => $status === TutelaStatus::ACTIVO ? 'success' : 'warning',
                    'message' => $status === TutelaStatus::ACTIVO
                        ? 'Conversão para tutela própria aprovada.'
                        : 'Conversão para tutela própria rejeitada.',
                ]);
        }

        // -------- Troca de tutela --------
        if ($tipo === 'troca_tutela') {
            $tenantTutorAnteriorId = $item->data['tenant_tutor_anterior_id'] ?? null;
            abort_unless((string) $tenantTutorAnteriorId === (string) tenancy()->tenant->getTenantKey(), 403);
            abort_if($shared->status !== TutelaStatus::PENDENTE_TROCA, 422, 'Esta troca já foi decidida.');

            $decisaoStatus = $status === TutelaStatus::ACTIVO
                ? 'aprovada_instituicao_anterior'
                : 'rejeitada';

            $item->data = array_merge($item->data, ['status' => $decisaoStatus]);
            $item->save();

            if ($status === TutelaStatus::ACTIVO) {
                $shared->update(['status' => TutelaStatus::PENDENTE]);
                $item->markAsRead();

                app(TutelaNotificationService::class)->aprovarTrocaTutela($shared);
                app(TutelaNotificationService::class)->notificarResultadoTroca(
                    $shared,
                    (string) $tenantTutorAnteriorId,
                    'aprovada',
                    'instituicao_anterior',
                );

                return Redirect::route('tenant.dashboard.notificacoes.show', $item->id)
                    ->with('toast', [
                        'type' => 'success',
                        'message' => 'A instituição anterior aprovou a troca. Aguardando aprovação da nova instituição.',
                    ]);
            }

            $shared->update(['status' => TutelaStatus::REJEITADO]);

            $sharedAnteriorId = $item->data['curso_tutelado_shared_anterior_id'] ?? null;

            if ($sharedAnteriorId) {
                CursoTuteladoShared::on($centralConnection)
                    ->whereKey($sharedAnteriorId)
                    ->where('status', TutelaStatus::ENCERRADO)
                    ->update(['status' => TutelaStatus::ACTIVO]);
            }

            app(TutelaNotificationService::class)->notificarRejeicaoTroca(
                $shared,
                (string) $tenantTutorAnteriorId,
            );

            $item->markAsRead();

            return Redirect::route('tenant.dashboard.notificacoes.show', $item->id)
                ->with('toast', [
                    'type' => 'warning',
                    'message' => 'Troca de tutela rejeitada.',
                ]);
        }

        // -------- Solicitação inicial de tutela --------
        abort_unless($shared->tenant_tutor_id === (string) tenancy()->tenant->getTenantKey(), 403);
        abort_if($shared->status !== TutelaStatus::PENDENTE, 422, 'Esta solicitação já foi decidida.');

        $shared->update(['status' => $status]);

        $tenantActualId = (string) tenancy()->tenant->getTenantKey();

        if (($item->data['troca_tutela_final'] ?? false) && $status === TutelaStatus::ACTIVO) {
            $sharedAnteriorId = $item->data['curso_tutelado_shared_anterior_id'] ?? null;

            if ($sharedAnteriorId) {
                CursoTuteladoShared::on($centralConnection)
                    ->whereKey($sharedAnteriorId)
                    ->where('status', TutelaStatus::ACTIVO)
                    ->update(['status' => TutelaStatus::ENCERRADO]);
            }

            $tenantTuteladoId = $shared->tenant_tutelado_id;
            tenancy()->initialize($tenantTuteladoId);

            $cursoTutelado = CursoTutelado::query()
                ->whereKey($shared->curso_tutelado_tutelado_id)
                ->first();

            if ($cursoTutelado) {
                $cursoTutelado->forceFill([
                    'curso_tutelado_shared_id' => $shared->getKey(),
                    'tipo_tutela' => 'externa',
                    'instituicao_tutora_id' => null,
                ])->save();
            }

            tenancy()->initialize($tenantActualId);
        }

        app(TutelaNotificationService::class)->notificarResultadoTroca(
            $shared,
            $tenantActualId,
            $status === TutelaStatus::ACTIVO ? 'aprovada' : 'rejeitada',
            'instituicao_nova',
        );

        $item->markAsRead();

        return Redirect::route('tenant.dashboard.notificacoes.show', $item->id)
            ->with('toast', [
                'type' => 'success',
                'message' => $status === TutelaStatus::ACTIVO
                    ? 'Tutela aprovada com sucesso.'
                    : 'Solicitação de tutela rejeitada.',
            ]);
    }
}
