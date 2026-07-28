<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NiveisEnsinoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveis = [
            [
                'nome' => 'Primário',
                'ordem' => 0,
            ],
            [
                'nome' => 'Iº Ciclo',
                'ordem' => 1,
            ],
            [
                'nome' => 'IIº Ciclo',
                'ordem' => 2,
            ],
        ];

        foreach ($niveis as $nivel) {
            DB::table('niveis_ensino')->insert([
                'id' => (string) Str::uuid7(),
                'nome' => $nivel['nome'],
                'ordem' => $nivel['ordem'],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
