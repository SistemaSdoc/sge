<?php

namespace Database\Seeders\Tenant;

use App\Models\tenant\Turno;
use Illuminate\Database\Seeder;

class TurnoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Turno::create([
            'nome' => 'Manhã',
        ]);
        Turno::create([
            'nome' => 'Tarde',
        ]);
        Turno::create([
            'nome' => 'Noite',
        ]);
    }
}
