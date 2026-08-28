<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Notifications\PropinaEmAtrasoNotification;
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
        $item = $request->user()->notifications()
            ->get()
            ->first(fn ($notification): bool => ($notification->data['tipo'] ?? null) === 'solicitacao_tutela'
                && ($notification->data['curso_tutelado_shared_id'] ?? null) === $shared);

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
        return $this->decidirTutela($request, $notification, 'activo');
    }

    public function rejeitarTutela(Request $request, string $notification)
    {
        return $this->decidirTutela($request, $notification, 'rejeitado');
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

        if (($data['tipo'] ?? null) === 'solicitacao_tutela' && ! empty($data['curso_tutelado_shared_id'])) {
            $centralConnection = config('tenancy.database.central_connection', config('database.default'));
            $data['status'] = CursoTuteladoShared::on($centralConnection)
                ->whereKey($data['curso_tutelado_shared_id'])
                ->value('status');
        }

        return [
            'id' => $notification->id,
            'tipo' => $notification->data['tipo'] ?? null,
            'titulo' => $notification->data['titulo'] ?? '',
            'mensagem' => $notification->data['mensagem'] ?? '',
            'dados' => $detalhada ? $data : [],
            'lida' => $notification->read_at !== null,
            'criada_em' => $notification->created_at->diffForHumans(),
            'criada_em_iso' => $notification->created_at->toISOString(),
        ];
    }

    private function decidirTutela(Request $request, string $notification, string $status)
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        abort_unless(($item->data['tipo'] ?? null) === 'solicitacao_tutela', 404);

        $sharedId = $item->data['curso_tutelado_shared_id'] ?? null;
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $shared = CursoTuteladoShared::on($centralConnection)->findOrFail($sharedId);

        abort_unless($shared->tenant_tutor_id === (string) tenancy()->tenant->getTenantKey(), 403);
        abort_if($shared->status !== 'pendente', 422, 'Esta solicitação já foi decidida.');

        $shared->update(['status' => $status]);
        $item->markAsRead();

        return Redirect::route('tenant.dashboard.notificacoes.show', $item->id)
            ->with('toast', [
                'type' => 'success',
                'message' => $status === 'activo'
                    ? 'Tutela aprovada com sucesso.'
                    : 'Solicitação de tutela rejeitada.',
            ]);
    }
}
