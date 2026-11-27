<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\AnoLectivo;
use Illuminate\Database\Seeder;

class AnoLectivoSeeder extends Seeder
{
    public function run(): void
    {
        AnoLectivo::updateOrCreate(
            ['nome' => '2025/2026'],
            [
                'data_inicio' => '2025-09-01',
                'data_fim' => '2026-07-31',
                'activo' => true,
                'estado' => 'planeado',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
