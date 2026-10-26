<?php

namespace Database\Seeders\Central;

use App\Models\Central\AnoLectivo;
use Illuminate\Database\Seeder;

class AnoLectivoSeeder extends Seeder
{
    public function run(): void
    {
        $agora = now();

        $anos = [
            ['nome' => '2023/2024', 'offset' => 0, 'duracao' => 5],
            ['nome' => '2024/2025', 'offset' => 5, 'duracao' => 5],
            ['nome' => '2025/2026', 'offset' => 10, 'duracao' => 5],
            ['nome' => '2026/2027', 'offset' => 15, 'duracao' => 525600],
        ];

        foreach ($anos as $ano) {
            AnoLectivo::query()->updateOrCreate(
                ['nome' => $ano['nome']],
                [
                    'data_inicio' => $agora->copy()->addMinutes($ano['offset']),
                    'data_fim' => $agora->copy()->addMinutes($ano['offset'] + $ano['duracao'])->setSecond(59),
                    'estado' => $ano['offset'] === 0 ? 'em_curso' : 'planeado',
                    'activo' => $ano['offset'] === 0,
                ],
            );
        }
    }
}
