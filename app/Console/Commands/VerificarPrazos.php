<?php

namespace App\Console\Commands;

use App\Models\PrazoProva;
use App\Notifications\PrazoProvaNotificacao;
use App\Services\PrazoNotificacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarPrazos extends Command
{
    protected $signature = 'prazos:verificar';
    protected $description = 'Avisa prazos a expirar (30 min) e marca os expirados';

    public function handle(PrazoNotificacaoService $notificacao): int
    {
        $this->avisarPrazosAExpirar($notificacao);
        $this->marcarExpirados($notificacao);

        return self::SUCCESS;
    }

    /**
     * Avisa prazos que expiram nos próximos 30 minutos.
     */
    private function avisarPrazosAExpirar(PrazoNotificacaoService $notificacao): void
    {
        $agora = now();
        $daquiA30Min = now()->addMinutes(30);

        $prazos = PrazoProva::where('status', 'aberto')
            ->where('aviso_expiracao_enviado', false)
            ->whereBetween('data_limite', [$agora, $daquiA30Min])
            ->with(['disciplina', 'classe'])
            ->get();

        if ($prazos->isEmpty()) {
            return;
        }

        foreach ($prazos as $prazo) {
            $notificacao->notificarProfessores($prazo, PrazoProvaNotificacao::TIPO_A_EXPIRAR);

            $prazo->update(['aviso_expiracao_enviado' => true]);

            $this->info("Aviso enviado: {$prazo->titulo} (expira às {$prazo->data_limite->format('H:i')})");

            Log::info('Aviso de expiração enviado', ['prazo_id' => $prazo->id]);
        }
    }

    /**
     * Marca prazos expirados e notifica.
     */
    private function marcarExpirados(PrazoNotificacaoService $notificacao): void
{
    $prazos = PrazoProva::where('status', 'aberto')
        ->where('data_limite', '<', now())
        ->with(['disciplina', 'classe'])
        ->get();

    if ($prazos->isEmpty()) {
        return;
    }

    foreach ($prazos as $prazo) {
        //  1. Notificar professores (expirado)
        $notificacao->notificarProfessores($prazo, PrazoProvaNotificacao::TIPO_EXPIRADO);

        //  2. Notificar diretores com estatísticas
        $notificacao->notificarDiretoresPrazoExpirado($prazo);

        // 3. Atualizar o status
        $prazo->update(['status' => 'expirado']);

        $this->info("Prazo expirado: {$prazo->titulo}");
        Log::info('Prazo expirado', ['prazo_id' => $prazo->id]);
    }
}
}