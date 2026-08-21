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
            'nome' => 'SDOCA',
            'email' => 'sdoca@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'telefone' => '900000000',
        ]);

        $superAdmin->assignRole('SuperAdmin');
    }
}
