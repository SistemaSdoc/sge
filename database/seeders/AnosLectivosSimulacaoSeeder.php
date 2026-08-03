<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use Illuminate\Database\Seeder;

class AnosLectivosSimulacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        // Cria todos os anos com intervalos de 15 minutos entre eles
        $anos = [
            ['nome' => '2026/2027', 'offset' => 0,   'duracao' => 15],  // agora - agora+15
            ['nome' => '2027/2028', 'offset' => 15,  'duracao' => 15],  // agora+15 - agora+30
            ['nome' => '2028/2029', 'offset' => 30,  'duracao' => 15],  // agora+30 - agora+45
            ['nome' => '2029/2030', 'offset' => 45,  'duracao' => 15],  // agora+45 - agora+60
            ['nome' => '2030/2031', 'offset' => 60,  'duracao' => 120], // agora+60 - agora+180 (2h)
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
            $fim = $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->format('H:i');
            $this->command->info("✓ {$ano['nome']}: {$inicio} - {$fim}");
        }

        $this->command->info("\n📋 Todos os 5 anos criados!");
        $this->command->info('Scheduler vai sincronizar a cada minuto.');
    }
}
