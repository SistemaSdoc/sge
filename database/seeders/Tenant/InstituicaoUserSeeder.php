<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstituicaoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instituicao::all()->each(function (Instituicao $instituicao) {
            $sigla = strtolower($instituicao->sigla);

            $this->createUser(
                nome: 'Director '.$instituicao->sigla,
                email: 'director.'.$sigla.'@'.'gestao.ao',
                telefone: '923000001',
                instituicao: $instituicao,
                role: 'Director',
            );

            $this->createUser(
                nome: 'Secretaria '.$instituicao->sigla,
                email: 'secretaria.'.$sigla.'@'.'gestao.ao',
                telefone: '923000002',
                instituicao: $instituicao,
                role: 'Secretaria',
            );
        });
    }

    private function createUser(
        string $nome,
        string $email,
        string $telefone,
        Instituicao $instituicao,
        string $role,
    ): void {
        $user = User::create([
            'id' => (string) Str::uuid7(),
            'nome' => $nome,
            'email' => strtolower($email),
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'telefone' => $telefone,
            'instituicao_id' => $instituicao->id,
        ]);

        $user->assignRole($role);
    }
}
