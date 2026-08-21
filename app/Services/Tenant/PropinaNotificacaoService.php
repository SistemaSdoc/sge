<?php

namespace App\Services\Tenant;

use App\Models\Tenant\User;
use App\Notifications\PropinaEmAtrasoNotification;
use Illuminate\Support\Facades\Log;

class PropinaNotificacaoService
{
    private const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    /**
     * Cria uma notificação de propina em atraso para o utilizador, mas
     * apenas se o estado da dívida (nº de meses + valor total, incluindo
     * multa) for diferente da última notificação já criada. Evita duplicar
     * notificações quando nada mudou desde o último aviso.
     *
     * Usado em três pontos: middleware (bloqueio ao aceder), anulação de
     * pagamento (dívida reaparece), e o job diário (multa passa a aplicar-se).
     */
    public function notificarSeEmAtraso(User $user, array $pendencias): void
    {
        if (empty($pendencias)) {
            return;
        }

        $totalPendencias = count($pendencias);
        $valorTotal = (float) collect($pendencias)->sum('valor');
        $assinatura = md5($totalPendencias.'-'.$valorTotal);

        Log::debug('[PropinaNotificacaoService] notificarSeEmAtraso - verificando', [
            'user_id' => $user->id,
            'total_pendencias' => $totalPendencias,
            'valor_total' => $valorTotal,
            'assinatura' => $assinatura,
        ]);

        $ultima = $user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->latest()
            ->first();

        if ($ultima && ($ultima->data['assinatura'] ?? null) === $assinatura) {
            Log::debug('[PropinaNotificacaoService] notificação já existe para este estado — não duplica', [
                'user_id' => $user->id,
                'assinatura' => $assinatura,
                'notificacao_existente_id' => $ultima->id,
            ]);
            return;
        }

        $meses = collect($pendencias)
            ->filter(fn ($p) => $p['mes'] !== null)
            ->map(fn ($p) => self::MESES[$p['mes']].'/'.$p['ano'])
            ->values()
            ->all();

        $user->notify(new PropinaEmAtrasoNotification($totalPendencias, $valorTotal, $meses, $assinatura));

        Log::info('[PropinaNotificacaoService] notificação criada', [
            'user_id' => $user->id,
            'total_pendencias' => $totalPendencias,
            'valor_total' => $valorTotal,
            'assinatura' => $assinatura,
        ]);
    }

    /**
     * Marca como lidas as notificações de propina em atraso ainda não lidas,
     * quando o aluno fica em dia (pendências vazias). Usado depois de um
     * pagamento ser registado.
     */
    public function resolverSePropinaEmDia(User $user, array $pendencias): void
    {
        if (! empty($pendencias)) {
            Log::debug('[PropinaNotificacaoService] resolverSePropinaEmDia - ainda tem dívida, não resolve', [
                'user_id' => $user->id,
                'meses_restantes' => count($pendencias),
            ]);
            return;
        }

        $marcadas = $user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Log::info('[PropinaNotificacaoService] resolverSePropinaEmDia - notificações resolvidas', [
            'user_id' => $user->id,
            'total_marcadas' => $marcadas,
        ]);
    }
}