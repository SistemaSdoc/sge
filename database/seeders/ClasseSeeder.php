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
            'nome' => 'Pré-escolar',
            'ordem' => 0,
        ]);

        Classe::create([
            'nome' => '1ª',
            'ordem' => 1,
        ]);

        Classe::create([
            'nome' => '2ª',
            'ordem' => 2,
        ]);

        Classe::create([
            'nome' => '3ª',
            'ordem' => 3,
        ]);

        Classe::create([
            'nome' => '4ª',
            'ordem' => 4,
        ]);

        Classe::create([
            'nome' => '5ª',
            'ordem' => 5,
        ]);

        Classe::create([
            'nome' => '6ª',
            'ordem' => 6,
        ]);

        Classe::create([
            'nome' => '7ª',
            'ordem' => 7,
        ]);

        Classe::create([
            'nome' => '8ª',
            'ordem' => 8,
        ]);

        Classe::create([
            'nome' => '9ª',
            'ordem' => 9,
        ]);

        Classe::create([
            'nome' => '10ª',
            'ordem' => 10,
        ]);

        Classe::create([
            'nome' => '11ª',
            'ordem' => 11,
        ]);

        Classe::create([
            'nome' => '12ª',
            'ordem' => 12,
        ]);

        Classe::create([
            'nome' => '13ª',
            'ordem' => 13,
        ]);
    }
}
