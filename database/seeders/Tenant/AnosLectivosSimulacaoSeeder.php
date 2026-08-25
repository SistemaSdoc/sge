<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\AnoLectivo;
use Illuminate\Database\Seeder;

class AnosLectivosSimulacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        // 2023/2024: começa agora, dura 5 min
        // 2024/2025: começa depois de 5 min, dura 5 min
        // 2025/2026: começa depois de 10 min, dura 5 min
        // 2026/2027: começa depois de 15 min, dura 1 ano (525600 min)

        $anos = [
            ['nome' => '2023/2024', 'offset' => 0,    'duracao' => 5],      // 0 - 5
            ['nome' => '2024/2025', 'offset' => 5,    'duracao' => 5],      // 5 - 10
            ['nome' => '2025/2026', 'offset' => 10,   'duracao' => 5],      // 10 - 15
            ['nome' => '2026/2027', 'offset' => 15,   'duracao' => 525600], // 15 - 525615 (1 ano)
        ];

        foreach ($anos as $ano) {
            AnoLectivo::updateOrCreate(
                ['nome' => $ano['nome']],
                [
                    'data_inicio' => $agora->copy()->addMinutes($ano['offset']),
                    'data_fim' => $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->setSecond(59),
                    'estado' => $ano['offset'] === 0 ? 'em_curso' : 'planeado',
                    'activo' => $ano['offset'] === 0,
                ],
            );

            $inicio = $agora->copy()->addMinutes($ano['offset'])->format('H:i');
            $fim = $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->format('H:i d/m');
            $this->command->info("✓ {$ano['nome']}: {$inicio} - {$fim} ({$ano['duracao']} min)");
        }

        $this->command->info("\n📋 Todos os 4 anos criados!");
        $this->command->info('Scheduler vai sincronizar a cada minuto.');
    }
}
