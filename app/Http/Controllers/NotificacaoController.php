<?php

namespace App\Http\Controllers;

use App\Notifications\PropinaEmAtrasoNotification;
use App\Services\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    /**
     * Lista as notificações do utilizador autenticado (JSON).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['notificacoes' => [], 'nao_lidas' => 0]);
        }

        // Limpeza opcional (nunca rebenta a request)
        $this->limparNotificacoesResolvidas($user);

        try {
            $notificacoes = $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($n) => $this->formatarNotificacao($n));

            return response()->json([
                'notificacoes' => $notificacoes,
                'nao_lidas'    => $user->unreadNotifications()->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar notificações', [
                'user_id' => $user->id,
                'erro'    => $e->getMessage(),
                'linha'   => $e->getLine(),
                'ficheiro'=> $e->getFile(),
            ]);

            return response()->json([
                'notificacoes' => [],
                'nao_lidas'    => 0,
                'erro'         => $e->getMessage(),
            ]);
        }
    }

    public function marcarLida(Request $request, string $id)
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();
        return back();
    }

    public function marcarTodasLidas(Request $request)
    {
        $user = $request->user();

        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Endpoint de diagnóstico — acede a /notificacoes/diagnostico
     */
    public function diagnostico(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id'              => $user->id,
            'user_class'           => get_class($user),
            'tem_notifiable'       => in_array(
                \Illuminate\Notifications\Notifiable::class,
                class_uses_recursive($user)
            ),
            'tem_metodo_notif'     => method_exists($user, 'notifications'),
            'tem_relacao_aluno'    => method_exists($user, 'aluno'),
            'tabela_notifications' => \Illuminate\Support\Facades\Schema::hasTable('notifications'),
            'total_notificacoes'   => method_exists($user, 'notifications')
                ? $user->notifications()->count()
                : 'N/A',
            'nao_lidas'            => method_exists($user, 'unreadNotifications')
                ? $user->unreadNotifications()->count()
                : 'N/A',
        ]);
    }

    // ============================================================
    // FORMATAÇÃO
    // ============================================================

    private function formatarNotificacao($notificacao): array
    {
        // Garantir que data é array
        $data = $notificacao->data;

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        if (!is_array($data)) {
            $data = [];
        }

        $tipo = $data['tipo'] ?? 'geral';

        $base = [
            'id'        => $notificacao->id,
            'tipo'      => $tipo,
            'titulo'    => $data['titulo'] ?? 'Notificação',
            'mensagem'  => $data['mensagem'] ?? '',
            'lida'      => $notificacao->read_at !== null,
            'criada_em' => $notificacao->created_at
                ? $notificacao->created_at->diffForHumans()
                : '',
            'url'       => $data['url'] ?? null,
        ];

        return array_merge($base, $this->detalhesPorTipo($tipo, $data));
    }

    private function detalhesPorTipo(string $tipo, array $data): array
    {
        return match ($tipo) {
            // ===== PROFESSOR =====
            'criado' => [
                'titulo'      => 'Novo prazo de prova',
                'mensagem'    => ($data['titulo'] ?? '') . ' — ' . ($data['disciplina'] ?? 'Todas') . ' (' . ($data['classe'] ?? 'Todas') . ')',
                'prazo_id'    => $data['prazo_id'] ?? null,
                'disciplina'  => $data['disciplina'] ?? null,
                'classe'      => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
                'periodo'     => $data['periodo'] ?? null,
            ],

            'prorrogado' => [
                'titulo'      => 'Prazo prorrogado',
                'mensagem'    => 'Nova data limite: ' . ($data['data_limite'] ?? ''),
                'prazo_id'    => $data['prazo_id'] ?? null,
                'disciplina'  => $data['disciplina'] ?? null,
                'classe'      => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'a_expirar' => [
                'titulo'      => 'Prazo a expirar',
                'mensagem'    => 'Faltam menos de 30 minutos para o prazo terminar.',
                'prazo_id'    => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'expirado' => [
                'titulo'      => 'Prazo expirado',
                'mensagem'    => 'O prazo já não aceita submissões.',
                'prazo_id'    => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'fechado' => [
                'titulo'      => 'Prazo encerrado',
                'mensagem'    => 'O prazo foi encerrado manualmente pelo diretor.',
                'prazo_id'    => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],

            'justificativa_avaliada' => [
                'titulo'           => ($data['status'] ?? null) === 'aceita'
                                        ? 'Justificativa aceite'
                                        : 'Justificativa recusada',
                'mensagem'         => 'A sua justificativa foi ' . ($data['status_label'] ?? $data['status'] ?? '') . '.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id'         => $data['prazo_id'] ?? null,
                'status'           => $data['status'] ?? null,
                'status_label'     => $data['status_label'] ?? null,
                'parecer_diretor'  => $data['parecer_diretor'] ?? null,
            ],

            'submissao_avaliada' => [
                'titulo'          => ($data['estado'] ?? null) === 'aprovado'
                                        ? 'Submissão aprovada'
                                        : 'Submissão rejeitada',
                'mensagem'        => 'A sua submissão para "' . ($data['prazo_titulo'] ?? 'prazo') . '" foi ' . ($data['estado_label'] ?? $data['estado'] ?? '') . '.',
                'submissao_id'    => $data['submissao_id'] ?? null,
                'prazo_id'        => $data['prazo_id'] ?? null,
                'estado'          => $data['estado'] ?? null,
                'estado_label'    => $data['estado_label'] ?? null,
                'parecer_diretor' => $data['parecer_diretor'] ?? null,
                'versao'          => $data['versao'] ?? null,
                'turma'           => $data['turma'] ?? null,
                'disciplina'      => $data['disciplina'] ?? null,
                'classe'          => $data['classe'] ?? null,
            ],

            // ===== DIRETOR =====
            'prazo_expirado' => [
                'titulo'      => 'Prazo expirado',
                'mensagem'    => ($data['titulo'] ?? '') . ' — ' . ($data['submeteram'] ?? 0) . '/' . ($data['total_professores'] ?? 0) . ' professores submeteram',
                'prazo_id'    => $data['prazo_id'] ?? null,
                'stats'       => [
                    'total'          => $data['total_professores'] ?? 0,
                    'submeteram'     => $data['submeteram'] ?? 0,
                    'nao_submeteram' => $data['nao_submeteram'] ?? 0,
                    'justificaram'   => $data['justificaram'] ?? 0,
                ],
            ],

            'justificativa_enviada' => [
                'titulo'           => 'Nova justificativa',
                'mensagem'         => ($data['professor_nome'] ?? 'Professor') . ' enviou uma justificativa.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id'         => $data['prazo_id'] ?? null,
                'professor_nome'   => $data['professor_nome'] ?? null,
                'motivo'           => $data['motivo'] ?? null,
            ],

            'nova_submissao' => [
                'titulo'         => 'Nova submissão recebida',
                'mensagem'       => ($data['professor_nome'] ?? 'Professor') . ' submeteu "' . ($data['prazo_titulo'] ?? 'prazo') . '".',
                'submissao_id'   => $data['submissao_id'] ?? null,
                'prazo_id'       => $data['prazo_id'] ?? null,
                'professor_nome' => $data['professor_nome'] ?? null,
                'prazo_titulo'   => $data['prazo_titulo'] ?? null,
                'disciplina'     => $data['disciplina'] ?? null,
                'classe'         => $data['classe'] ?? null,
                'turma'          => $data['turma'] ?? null,
                'versao'         => $data['versao'] ?? null,
            ],

            // ===== PROPINAS =====
            'propina_atraso' => [
                'titulo'      => 'Propina em atraso',
                'mensagem'    => 'Tem propinas em atraso. Regularize para continuar a aceder.',
                'meses'       => $data['meses'] ?? [],
                'valor_total' => $data['valor_total'] ?? null,
            ],

            // ===== DEFAULT =====
            default => [
                'titulo'   => $data['titulo'] ?? 'Notificação',
                'mensagem' => $data['mensagem'] ?? '',
            ],
        };
    }

    // ============================================================
    // LIMPEZA
    // ============================================================

    private function limparNotificacoesResolvidas($user): void
    {
        if (!method_exists($user, 'aluno')) {
            return;
        }

        try {
            $aluno = $user->aluno;
            if (!$aluno) {
                return;
            }

            if (!method_exists($this->verificador, 'pendenciasDoAluno')) {
                return;
            }

            $pendenciasAtuais = $this->verificador->pendenciasDoAluno($aluno);
            $assinaturaAtual = md5(
                count($pendenciasAtuais) . '-' . collect($pendenciasAtuais)->sum('valor')
            );

            $notificacoesPropina = $user->notifications()
                ->where('type', PropinaEmAtrasoNotification::class)
                ->get();

            foreach ($notificacoesPropina as $n) {
                $assinaturaNotificacao = $n->data['assinatura'] ?? null;
                $resolvida = empty($pendenciasAtuais)
                    || $assinaturaNotificacao !== $assinaturaAtual;

                if ($resolvida) {
                    $n->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao limpar notificações resolvidas', [
                'user_id' => $user->id,
                'erro'    => $e->getMessage(),
            ]);
        }
    }
}