<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnoLectivoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ano_lectivos')->updateOrInsert(
            ['nome' => '2025/2026'],
            [
                'id' => (string) Str::uuid7(),
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
