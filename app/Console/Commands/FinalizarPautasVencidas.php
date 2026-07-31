<?php

namespace App\Console\Commands;

use App\Models\AnoLectivo;
use App\Models\PautaStatus;
use App\Models\PeriodoLancamentoNotas;
use Illuminate\Console\Command;

class FinalizarPautasVencidas extends Command
{
    protected $signature = 'pautas:finalizar-vencidas';

    public function handle(): void
    {
        $prazosEncerrados = PeriodoLancamentoNotas::where('data_limite', '<', today())->get();

        foreach ($prazosEncerrados as $prazo) {
            // Buscar todos os TDPs da instituição neste período ainda em rascunho
            PautaStatus::where('periodo', $prazo->periodo)
                ->where('status', 'rascunho')
                ->whereHas('turmaDisciplinaProfessor.turma', function ($q) use ($prazo) {
                    $q->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado', function ($q2) use ($prazo) {
                        $q2->where('instituicao_id', $prazo->instituicao_id);
                    });
                })
                ->each(function (PautaStatus $ps) {
                    $ps->update([
                        'status' => 'finalizada',
                        'finalizada_em' => now(),
                        'finalizada_automaticamente' => true,
                    ]);

                    // Notificar professor
                    // $ps->turmaDisciplinaProfessor->professor->user->notify(...)
                });
        }
    }
}