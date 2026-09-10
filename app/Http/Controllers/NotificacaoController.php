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

    public function index(Request $request)
    {
        $user = $request->user();

        $this->limparNotificacoesResolvidas($user);

        $notificacoes = $user->notifications()
            ->latest()
            ->get()
            ->filter(function ($n) use ($user) {
                $categoria = $n->data['categoria'] ?? 'aluno';

                if ($user->hasRole('Aluno')) {
                    return $categoria === 'aluno';
                }

                if ($user->instituicao?->tipo === 'colegio') {
                    return $categoria === 'instituicao';
                }

                if ($user->instituicao?->tipo === 'instituto') {
                    return $categoria === 'tutela';
                }

                return true;
            })
            ->values() // reindexa as chaves — sem isto, filter() deixa "buracos" nas chaves
            // e response()->json() serializa como objeto {} em vez de array [] quando
            // as chaves não são sequenciais, quebrando o notificacoes.filter() no frontend.
            ->take(20)
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? null,
                'categoria' => $n->data['categoria'] ?? 'aluno',
                'titulo' => $n->data['titulo'] ?? '',
                'mensagem' => $n->data['mensagem'] ?? '',
                'meses' => $n->data['meses'] ?? [],
                'valor_total' => $n->data['valor_total'] ?? null,
                'url' => $n->data['url'] ?? null,
                'lida' => $n->read_at !== null,
                'criada_em' => $n->created_at->diffForHumans(),
            ])
            ->values(); // idem — garante que o resultado final é sempre um array JSON

        Log::debug('NotificacaoController@index', [
            'user_id' => $user->id,
            'total' => $notificacoes->count(),
        ]);

        return response()->json([
            'notificacoes' => $notificacoes,
            'nao_lidas' => $notificacoes->where('lida', false)->count(),
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

    public function destroy(Request $request, string $id)
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->delete();

        Log::debug('NotificacaoController@destroy', ['notificacao_id' => $id]);

        return response()->noContent();
    }
}