<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['nome' => 'Master']);

        Role::firstOrCreate(['nome' => 'Director']);

        Role::firstOrCreate(['nome' => 'Secretaria']);

        Role::firstOrCreate(['nome' => 'Professor']);

        Role::firstOrCreate(['nome' => 'Aluno']);

        Role::firstOrCreate(['nome' => 'Candidato']);
    }
}
