<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Notifications\PropinaEmAtrasoNotification;
use App\Services\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        return response()->json([
            'notificacoes' => $notificacoes,
            'nao_lidas' => $user->unreadNotifications()->count(),
        ]);
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
}
