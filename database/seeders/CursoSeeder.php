<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Curso::create([
            'nome' => 'Informática de Gestão',
            'descricao' => 'Curso de Informática de Gestão',
            'duracao_anos' => 4,
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);

        Curso::create([
            'nome' => 'Gestão Administrativa',
            'descricao' => 'Curso de Gestão Administrativa',
            'duracao_anos' => 4,
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);

        Curso::create([
            'nome' => 'Contabilidade',
            'descricao' => 'Curso de Contabilidade',
            'duracao_anos' => 4,
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);
    }
}
