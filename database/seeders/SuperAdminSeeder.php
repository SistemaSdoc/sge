<?php

namespace Database\Seeders;

use App\Models\Central\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'id' => (string) Str::uuid7(),
            'nome' => 'Super Admin',
            'email' => 'super@sge.ao',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'telefone' => '900000000',
            'instituicao_id' => null,
        ]);

        $superAdmin->assignRole('SuperAdmin');
    }
}
