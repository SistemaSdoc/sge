<?php

namespace Database\Seeders;

use App\Models\AnoLectivo;
use Illuminate\Database\Seeder;

class AnosLectivosSimulacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        // Cria todos os 4 anos com intervalos de 2 minutos
        $anos = [
            ['nome' => '2026/2027', 'offset' => 0],   // agora - agora+2
            ['nome' => '2027/2028', 'offset' => 5],   // agora+2 - agora+4
            ['nome' => '2028/2029', 'offset' => 10],   // agora+4 - agora+6
            ['nome' => '2029/2030', 'offset' => 15],   // agora+6 - agora+8
            ['nome' => '2030/2031', 'offset' => 20],   // agora+8 - agora+10
        ];

        foreach ($anos as $ano) {
            AnoLectivo::create([
                'nome' => $ano['nome'],
                'data_inicio' => $agora->copy()->addMinutes($ano['offset']),
                'data_fim' => $agora->copy()->addMinutes($ano['offset'] + 5)->setSecond(59),
                'estado' => $ano['offset'] === 0 ? 'em_curso' : 'planeado',
                'activo' => $ano['offset'] === 0,
            ]);

            $inicio = $agora->copy()->addMinutes($ano['offset'])->format('H:i');
            $fim = $agora->copy()->addMinutes($ano['offset'] + 5)->format('H:i');
            $this->command->info("✓ {$ano['nome']}: {$inicio} - {$fim}");
        }

        $this->command->info("\n📋 Todos os 5 anos criados!");
        $this->command->info('Scheduler vai sincronizar a cada minuto.');
    }
}
