<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use Illuminate\Database\Seeder;

class AnosLectivosSimulacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        // 2026/2027: começa agora, dura 10 min
        // 2027/2028: começa depois de 10 min, dura 10 min
        // 2028/2029: começa depois, dura 10 min
        // 2029/2030: começa depois, dura 24h
        // 2030/2031: começa depois de 24h, dura 10 min

        $anos = [
            ['nome' => '2026/2027', 'offset' => 0, 'duracao' => 10],      // 0 - 10
            ['nome' => '2027/2028', 'offset' => 10, 'duracao' => 10],     // 10 - 20
            ['nome' => '2028/2029', 'offset' => 20, 'duracao' => 10],     // 20 - 30
            ['nome' => '2029/2030', 'offset' => 30, 'duracao' => 1440],   // 30 - 1470 (24h)
            ['nome' => '2030/2031', 'offset' => 1470, 'duracao' => 10],   // 1470 - 1480
        ];

        foreach ($anos as $ano) {
            AnoLectivo::create([
                'nome' => $ano['nome'],
                'data_inicio' => $agora->copy()->addMinutes($ano['offset']),
                'data_fim' => $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->setSecond(59),
                'estado' => $ano['offset'] === 0 ? 'em_curso' : 'planeado',
                'activo' => $ano['offset'] === 0,
            ]);

            $inicio = $agora->copy()->addMinutes($ano['offset'])->format('H:i');
            $fim = $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->format('H:i d/m');
            $this->command->info("✓ {$ano['nome']}: {$inicio} - {$fim} ({$ano['duracao']} min)");
        }

        $this->command->info("\n📋 Todos os 5 anos criados!");
        $this->command->info('Scheduler vai sincronizar a cada minuto.');
    }
}
