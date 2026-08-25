<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Turno;
use Illuminate\Database\Seeder;

class TurnoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Turno::firstOrCreate(['nome' => 'Manhã']);
        Turno::firstOrCreate(['nome' => 'Tarde']);
        Turno::firstOrCreate(['nome' => 'Noite']);
    }
}
