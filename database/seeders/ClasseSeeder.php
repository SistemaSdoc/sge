<?php

namespace Database\Seeders;

use App\Models\Classe;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Classe::create([
            'nome' => '10ª',
            'ordem' => 1,
        ]);
        Classe::create([
            'nome' => '11ª',
            'ordem' => 2,
        ]);

        Classe::create([
            'nome' => '12ª',
            'ordem' => 3,
        ]);
        Classe::create([
            'nome' => '13ª',
            'ordem' => 4,
        ]);
    }
}
