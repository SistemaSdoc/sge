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
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    // ============================================================
    // LISTAGEM / SINO
    // ============================================================

    /**
     * Lista as notificações.
     *
     * - Requisição Inertia (X-Inertia) -> renderiza página
     * - Requisição fetch do sino       -> JSON
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['notificacoes' => [], 'nao_lidas' => 0]);
        }

        $this->limparNotificacoesResolvidas($user);

        try {
            $notificacoes = $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($n) => $this->formatarNotificacao($n))
                ->values();

            $naoLidas = $user->unreadNotifications()->count();
        } catch (\Throwable $e) {
            Log::error('Erro ao listar notificações', [
                'user_id' => $user->id,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'ficheiro' => $e->getFile(),
            ]);

            $notificacoes = collect();
            $naoLidas = 0;
        }

        if ($request->header('X-Inertia')) {
            return Inertia::render('tenant/notificacoes/index', [
                'notificacoes' => $notificacoes,
                'naoLidas' => $naoLidas,
            ]);
        }

        return response()->json([
            'notificacoes' => $notificacoes,
            'nao_lidas' => $naoLidas,
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
            'notificacao' => $this->serializar($item, true),
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
            $sharedId = $this->resolverSharedIdDaNotificacao($item, $centralConnection);
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

    private function resolverSharedIdDaNotificacao(object $notification, string $centralConnection): ?string
    {
        $data = $notification->data;

        return CursoTuteladoShared::on($centralConnection)
            ->where('curso_nome', $data['curso_nome'] ?? '')
            ->where('tenant_tutor_id', (string) tenancy()->tenant->getTenantKey())
            ->latest('updated_at')
            ->value('id');
    }

    // ============================================================
    // LIMPEZA DE NOTIFICAÇÕES RESOLVIDAS (PROPINAS)
    // ============================================================

    private function limparNotificacoesResolvidas($user): void
    {
        if (! method_exists($user, 'aluno')) {
            return;
        }

        try {
            $aluno = $user->aluno;
            if (! $aluno) {
                return;
            }

            if (! method_exists($this->verificador, 'pendenciasDoAluno')) {
                return;
            }

            $pendenciasAtuais = $this->verificador->pendenciasDoAluno($aluno);
            $assinaturaAtual = md5(
                count($pendenciasAtuais).'-'.collect($pendenciasAtuais)->sum('valor')
            );

            $notificacoesPropina = $user->notifications()
                ->where('type', PropinaEmAtrasoNotification::class)
                ->get();

            foreach ($notificacoesPropina as $n) {
                $assinaturaNotificacao = $n->data['assinatura'] ?? null;

                $resolvida = empty($pendenciasAtuais)
                    || $assinaturaNotificacao !== $assinaturaAtual;

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
        } catch (\Throwable $e) {
            Log::warning('Falha ao limpar notificações resolvidas', [
                'user_id' => $user->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // FORMATAÇÃO — SINO (JSON)
    // ============================================================

    private function formatarNotificacao($notificacao): array
    {
        $data = $notificacao->data;

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        if (! is_array($data)) {
            $data = [];
        }

        $tipo = $data['tipo'] ?? 'geral';

        $base = [
            'id' => $notificacao->id,
            'tipo' => $tipo,
            'titulo' => $data['titulo'] ?? 'Notificação',
            'mensagem' => $data['mensagem'] ?? '',
            'lida' => $notificacao->read_at !== null,
            'criada_em' => $notificacao->created_at
                ? $notificacao->created_at->diffForHumans()
                : '',
            'url' => $data['url'] ?? null,
        ];

        return array_merge($base, $this->detalhesPorTipo($tipo, $data));
    }

    private function detalhesPorTipo(string $tipo, array $data): array
    {
        return match ($tipo) {
            // ===== PROFESSOR =====
            'criado' => [
                'titulo' => 'Novo prazo de prova',
                'mensagem' => ($data['titulo'] ?? '').' — '.($data['disciplina'] ?? 'Todas').' ('.($data['classe'] ?? 'Todas').')',
                'prazo_id' => $data['prazo_id'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
                'periodo' => $data['periodo'] ?? null,
            ],

            'prorrogado' => [
                'titulo' => 'Prazo prorrogado',
                'mensagem' => 'Nova data limite: '.($data['data_limite'] ?? ''),
                'prazo_id' => $data['prazo_id'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'a_expirar' => [
                'titulo' => 'Prazo a expirar',
                'mensagem' => 'Faltam menos de 30 minutos para o prazo terminar.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'expirado' => [
                'titulo' => 'Prazo expirado',
                'mensagem' => 'O prazo já não aceita submissões.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'fechado' => [
                'titulo' => 'Prazo encerrado',
                'mensagem' => 'O prazo foi encerrado manualmente pelo diretor.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'justificativa_avaliada' => [
                'titulo' => ($data['status'] ?? null) === 'aceita'
                                        ? 'Justificativa aceite'
                                        : 'Justificativa recusada',
                'mensagem' => 'A sua justificativa foi '.($data['status_label'] ?? $data['status'] ?? '').'.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'status' => $data['status'] ?? null,
                'status_label' => $data['status_label'] ?? null,
                'parecer_diretor' => $data['parecer_diretor'] ?? null,
            ],

            'submissao_avaliada' => [
                'titulo' => ($data['estado'] ?? null) === 'aprovado'
                                        ? 'Submissão aprovada'
                                        : 'Submissão rejeitada',
                'mensagem' => 'A sua submissão para "'.($data['prazo_titulo'] ?? 'prazo').'" foi '.($data['estado_label'] ?? $data['estado'] ?? '').'.',
                'submissao_id' => $data['submissao_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'estado' => $data['estado'] ?? null,
                'estado_label' => $data['estado_label'] ?? null,
                'parecer_diretor' => $data['parecer_diretor'] ?? null,
                'versao' => $data['versao'] ?? null,
                'turma' => $data['turma'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
            ],

            // ===== DIRETOR =====
            'prazo_expirado' => [
                'titulo' => 'Prazo expirado',
                'mensagem' => ($data['titulo'] ?? '').' — '.($data['submeteram'] ?? 0).'/'.($data['total_professores'] ?? 0).' professores submeteram',
                'prazo_id' => $data['prazo_id'] ?? null,
                'stats' => [
                    'total' => $data['total_professores'] ?? 0,
                    'submeteram' => $data['submeteram'] ?? 0,
                    'nao_submeteram' => $data['nao_submeteram'] ?? 0,
                    'justificaram' => $data['justificaram'] ?? 0,
                ],
            ],

            'justificativa_enviada' => [
                'titulo' => 'Nova justificativa',
                'mensagem' => ($data['professor_nome'] ?? 'Professor').' enviou uma justificativa.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'professor_nome' => $data['professor_nome'] ?? null,
                'motivo' => $data['motivo'] ?? null,
            ],

            'nova_submissao' => [
                'titulo' => 'Nova submissão recebida',
                'mensagem' => ($data['professor_nome'] ?? 'Professor').' submeteu "'.($data['prazo_titulo'] ?? 'prazo').'".',
                'submissao_id' => $data['submissao_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'professor_nome' => $data['professor_nome'] ?? null,
                'prazo_titulo' => $data['prazo_titulo'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'turma' => $data['turma'] ?? null,
                'versao' => $data['versao'] ?? null,
            ],

            // ===== PROPINAS =====
            'propina_atraso' => [
                'titulo' => 'Propina em atraso',
                'mensagem' => 'Tem propinas em atraso. Regularize para continuar a aceder.',
                'meses' => $data['meses'] ?? [],
                'valor_total' => $data['valor_total'] ?? null,
            ],
            // ===== PERFIL =====
            'perfil_incompleto' => [
                'titulo' => 'Alerta! Complete o seu perfil',
                'mensagem' => 'O seu acesso está limitado. Preencha os dados obrigatórios para continuar.',
                'url' => '/dashboard/settings/profile',
            ],

            // ===== DEFAULT =====
            default => [
                'titulo' => $data['titulo'] ?? 'Notificação',
                'mensagem' => $data['mensagem'] ?? '',
            ],
        };
    }

    // ============================================================
    // SERIALIZAÇÃO — PÁGINA INERTIA (DETALHE)
    // ============================================================

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
}
