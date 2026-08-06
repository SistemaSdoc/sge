<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificacaoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

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