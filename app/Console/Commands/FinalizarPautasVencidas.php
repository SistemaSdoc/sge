<?php

namespace App\Console\Commands;

use App\Models\Tenant\PautaStatus;
use App\Models\Tenant\PeriodoLancamentoNotas;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Console\Command;

class FinalizarPautasVencidas extends Command
{
    protected $signature = 'pautas:finalizar-vencidas';

    protected $description = 'Expira pautas em rascunho com prazo encerrado e notifica professores';

    public function handle(): void
    {
        $agora = now();

        // ── 1. Expirar pautas com prazo encerrado ──────────────────
        PeriodoLancamentoNotas::where('data_limite', '<', $agora)
            ->get()

            // Versão sem precisar da relação no PautaStatus
            ->each(function (PeriodoLancamentoNotas $prazo) use ($agora) {
                // Busca os TDP ids da instituição primeiro
                $tdpIds = TurmaDisciplinaProfessor::whereHas(
                    'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                    fn ($q) => $q->where('instituicao_tutora_id', $prazo->instituicao_id)
                )->pluck('id');

                PautaStatus::whereIn('turma_disciplina_professor_id', $tdpIds)
                    ->where('periodo', $prazo->periodo)
                    ->where('status', 'rascunho')
                    ->each(function (PautaStatus $ps) use ($agora) {
                        $ps->update([
                            'status' => 'expirada',
                            'finalizada_em' => $agora,
                            'finalizada_automaticamente' => true,
                        ]);
                    });
            });

        // ── 2. Notificar professores com prazo a expirar em breve ──
        PeriodoLancamentoNotas::whereBetween('data_limite', [$agora, $agora->copy()->addHours(24)])
            ->whereNull('notificado_em')
            ->get()
            ->each(function (PeriodoLancamentoNotas $prazo) use ($agora) {
                // TODO: notificar professores com rascunhos abertos
                // ...

                $prazo->update(['notificado_em' => $agora]);
            });

        $this->info('Concluído: '.$agora);
    }
}
