<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\NivelEnsino;
use Illuminate\Database\Seeder;

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
            NivelEnsino::updateOrCreate(
                ['nome' => $nivel['nome']],
                [
                    'ordem' => $nivel['ordem'],
                    'activo' => true,
                ],
            );
        }
    }
}
