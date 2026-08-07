<?php

namespace Database\Seeders;

use App\Models\Candidato;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CandidatoSeeder extends Seeder
{
    public function run(): void
    {
        $total = 30;

        for ($i = 1; $i <= $total; $i++) {
            $nome = fake('pt_PT')->name();
            $genero = fake()->randomElement(['M', 'F']);
            $nacionalidade = 'Angolana';
            $naturalidade = fake('pt_PT')->city();
            $filiacao = fake('pt_PT')->name().' e '.fake('pt_PT')->name();
            $dataNascimento = fake()->dateTimeBetween('-20 years', '-15 years')->format('Y-m-d');

            $user = User::create([
                'nome' => $nome,
                'email' => "candidato{$i}@sge.test",
                'password' => Hash::make('password'),
                'genero' => $genero,
                'nacionalidade' => $nacionalidade,
                'naturalidade' => $naturalidade,
                'filiacao' => $filiacao,
                'data_nascimento' => $dataNascimento,
            ]);

            Candidato::create([
                'nome' => $nome,
                'bi' => $this->gerarBiFicticio(),
                'numero_estudante' => 'CAND-'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'morada' => fake('pt_PT')->address(),
                'telefone' => '9'.fake()->numerify('########'),
                'email' => $user->email,
                'user_id' => $user->id,
                'genero' => $genero,
                'nacionalidade' => $nacionalidade,
                'naturalidade' => $naturalidade,
                'filiacao' => $filiacao,
                'data_nascimento' => $dataNascimento,
            ]);
        }
    }

    private function gerarBiFicticio(): string
    {
        // Formato aproximado de BI angolano: 9 dígitos + LA + 3 dígitos
        return fake()->numerify('#########').'LA'.fake()->numerify('###');
    }
}
