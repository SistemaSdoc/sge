<?php

namespace Database\Seeders;

use App\Models\Candidato;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CandidatoSeeder extends Seeder
{
    public function run(): void
    {
        $total = 30;

        for ($i = 1; $i <= $total; $i++) {
            $nome = fake('pt_PT')->name();

            $user = User::create([
                'nome' => $nome,
                'email' => "candidato{$i}@sge.test",
                'password' => Hash::make('password'),
            ]);

            Candidato::create([
                'nome' => $nome,
                'bi' => $this->gerarBiFicticio(),
                'numero_estudante' => 'CAND-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'morada' => fake('pt_PT')->address(),
                'telefone' => '9' . fake()->numerify('########'),
                'email' => $user->email,
                'user_id' => $user->id,
            ]);
        }
    }

    private function gerarBiFicticio(): string
    {
        // Formato aproximado de BI angolano: 9 dígitos + LA + 3 dígitos
        return fake()->numerify('#########') . 'LA' . fake()->numerify('###');
    }
}