<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use Illuminate\Database\Seeder;

class AnosLectivosSimulacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        // 2026/2027: começa agora, dura 5 min
        // 2027/2028: começa depois de 5 min, dura 5 min
        // 2028/2029: começa depois de 10 min, dura 24h (1440 min)
        // 2029/2030: começa depois de 24h+10 min, dura 10 min
        // 2030/2031: começa depois, dura 10 min

        $anos = [
            ['nome' => '2026/2027', 'offset' => 0,    'duracao' => 5],      // 0 - 5
            ['nome' => '2027/2028', 'offset' => 5,    'duracao' => 5],      // 5 - 10
            ['nome' => '2028/2029', 'offset' => 10,   'duracao' => 5],      // 10 - 15
            ['nome' => '2029/2030', 'offset' => 15,   'duracao' => 1440],   // 15 - 1455 (24h)
            ['nome' => '2030/2031', 'offset' => 1455, 'duracao' => 10],     // 1455 - 1465
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
