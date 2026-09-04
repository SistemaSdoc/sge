<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\TutelaStatus;
use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Notifications\PropinaEmAtrasoNotification;
use App\Services\Tenant\Tutela\TutelaNotificationService;
use App\Services\Tenant\Tutela\TutelaService;
use App\Services\Tenant\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $this->limparNotificacoesResolvidas($user);

        $notificacoes = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? null,
                'titulo' => $n->data['titulo'] ?? '',
                'mensagem' => $n->data['mensagem'] ?? '',
                'meses' => $n->data['meses'] ?? [],
                'valor_total' => $n->data['valor_total'] ?? null,
                'lida' => $n->read_at !== null,
                'criada_em' => $n->created_at->diffForHumans(),
            ]);

        Log::debug('NotificacaoController@index', [
            'user_id' => $user->id,
            'total' => $notificacoes->count(),
        ]);

        $payload = [
            'notificacoes' => $notificacoes,
            'nao_lidas' => $user->unreadNotifications()->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('tenant/notificacoes/index', [
            'notificacoes' => $user->notifications()->latest()->paginate(20)->through(
                fn ($notification): array => $this->serializar($notification)
            ),
            'naoLidas' => $payload['nao_lidas'],
        ]);
    }

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
                if (! in_array($notification->data['tipo'] ?? null, ['solicitacao_tutela', 'troca_tutela', 'conversao_tutela_propria', 'conversao_tutela_propria_pendente', 'troca_tutela_rejeitada', 'troca_tutela_resultado', 'conversao_tutela_propria_resultado'], true)) {
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
            'notificacao' => $this->serializar($item, true),
        ]);
    }

    public function aprovarTutela(Request $request, string $notification)
    {
        return $this->decidirTutela($request, $notification, TutelaStatus::ACTIVO);
    }

    public function rejeitarTutela(Request $request, string $notification)
    {
        return $this->decidirTutela($request, $notification, TutelaStatus::REJEITADO);
    }

    /**
     * Remove notificações de propina em atraso cuja dívida já foi paga.
     * Corre a cada carregamento do sino — barato, porque só há um aluno
     * por user e a verificação já é usada no middleware.
     */
    private function limparNotificacoesResolvidas($user): void
    {
        $aluno = $user->aluno;

        if (! $aluno) {
            return;
        }

        $pendenciasAtuais = $this->verificador->pendenciasDoAluno($aluno);
        $assinaturaAtual = md5(count($pendenciasAtuais).'-'.collect($pendenciasAtuais)->sum('valor'));

        $notificacoesPropina = $user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->get();

        foreach ($notificacoesPropina as $n) {
            $assinaturaNotificacao = $n->data['assinatura'] ?? null;

            // Se já não há pendências, ou se a assinatura da notificação
            // não bate com o estado actual da dívida, está resolvida.
            $resolvida = empty($pendenciasAtuais) || $assinaturaNotificacao !== $assinaturaAtual;

            if ($resolvida) {
                Log::debug('[NotificacaoController] a apagar notificação resolvida', [
                    'user_id' => $user->id,
                    'notificacao_id' => $n->id,
                    'assinatura_notificacao' => $assinaturaNotificacao,
                    'assinatura_atual' => $assinaturaAtual,
                ]);
                $n->delete();
            }
        }
    }

    public function marcarLida(Request $request, string $id)
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();

        Log::debug('NotificacaoController@marcarLida', ['notificacao_id' => $id]);

        return response()->noContent();
    }

    public function marcarTodasLidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        Log::debug('NotificacaoController@marcarTodasLidas', ['user_id' => $request->user()->id]);

        return response()->noContent();
    }

    private function serializar(object $notification, bool $detalhada = false): array
    {
        $data = $notification->data;
        $tipo = $data['tipo'] ?? null;
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        if (in_array($tipo, ['solicitacao_tutela', 'troca_tutela', 'conversao_tutela_propria'], true) && empty($data['curso_tutelado_shared_id'])) {
            $resolvedSharedId = $this->resolverSharedIdDaNotificacao($notification, $centralConnection);

            if ($resolvedSharedId) {
                $data['curso_tutelado_shared_id'] = $resolvedSharedId;
            }
        }

        if ($tipo === 'solicitacao_tutela' && ! empty($data['curso_tutelado_shared_id'])) {
            $data['status'] = CursoTuteladoShared::on($centralConnection)
                ->whereKey($data['curso_tutelado_shared_id'])
                ->value('status');
        }

        if ($tipo === 'troca_tutela' && ! isset($data['status'])) {
            $data['status'] = ! empty($data['curso_tutelado_shared_id'])
                ? CursoTuteladoShared::on($centralConnection)
                    ->whereKey($data['curso_tutelado_shared_id'])
                    ->value('status')
                : 'pendente_troca';
        }

        if ($tipo === 'conversao_tutela_propria_resultado' && ($data['resultado'] ?? null) === 'pendente') {
            $data['tipo'] = 'conversao_tutela_propria_pendente';
        }

        $data['status'] ??= 'pendente';

        return [
            'id' => $notification->id,
            'tipo' => $data['tipo'] ?? null,
            'titulo' => $data['titulo'] ?? '',
            'mensagem' => $data['mensagem'] ?? '',
            'dados' => $detalhada ? $data : [],
            'lida' => $notification->read_at !== null,
            'criada_em' => $notification->created_at->diffForHumans(),
            'criada_em_iso' => $notification->created_at->toISOString(),
        ];
    }

    private function decidirTutela(Request $request, string $notification, TutelaStatus $status)
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $tipo = $item->data['tipo'] ?? null;
        abort_unless(in_array($tipo, ['solicitacao_tutela', 'troca_tutela', 'conversao_tutela_propria'], true), 404);

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $sharedId = $item->data['curso_tutelado_shared_id'] ?? null;
        if (! $sharedId) {
            $sharedId = $this->resolverSharedIdDaNotificacao($item, $centralConnection);
        }

        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($sharedId);

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
                // A instituição antiga aprovou a troca; a nova ainda precisa aceitar.
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

            // REJEIÇÃO
            $shared->update(['status' => TutelaStatus::REJEITADO]);

            // O vínculo anterior continua activo; o curso local não é alterado.
            $sharedAnteriorId = $item->data['curso_tutelado_shared_anterior_id'] ?? null;

            if ($sharedAnteriorId) {
                CursoTuteladoShared::on($centralConnection)
                    ->whereKey($sharedAnteriorId)
                    ->where('status', TutelaStatus::ENCERRADO) // só toca se foi encerrado indevidamente
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

    private function resolverSharedIdDaNotificacao(object $notification, string $centralConnection): ?string
    {
        $data = $notification->data;

        return CursoTuteladoShared::on($centralConnection)
            ->where('curso_nome', $data['curso_nome'] ?? '')
            ->where('tenant_tutor_id', (string) tenancy()->tenant->getTenantKey())
            ->latest('updated_at')
            ->value('id');
    }
}
