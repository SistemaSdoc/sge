<?php

namespace Database\Seeders;

use App\Models\Instituicao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,

            // TABELAS BASE
            InstituicaoSeeder::class,
            CursoSeeder::class,
            ClasseSeeder::class,
            TurnoSeeder::class,
            DisciplinaSeeder::class,

            // RELACIONAMENTOS ACADÉMICOS
            InstituicaoCursoSeeder::class,
            CursoTuteladoSeeder::class,
            CursoClasseSeeder::class,
            CursoClasseTurnoSeeder::class,

            // TURMAS
            TurmaSeeder::class,
        ]);

        $instituicoes = Instituicao::all();

        foreach ($instituicoes as $instituicao) {
            // Director
            $director = User::create([
                'id' => (string) Str::uuid7(),
                'nome' => 'Director '.$instituicao->sigla,
                'email' => 'director'. $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'telefone' => '923000001',
                'instituicao_id' => $instituicao->id,
            ]);

            $director->assignRole('Director');

            // Secretaria
            $secretaria = User::create([
                'id' => (string) Str::uuid7(),
                'nome' => 'Secretaria '.$instituicao->sigla,
                'email' => 'secretaria.'. $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'telefone' => '923000002',
                'instituicao_id' => $instituicao->id,
            ]);

            $secretaria->assignRole('Secretaria');

            // Professor
            $professor = User::create([
                'id' => (string) Str::uuid7(),
                'nome' => 'Professor '.$instituicao->sigla,
                'email' => 'professor.'. $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'telefone' => '923000003',
                'instituicao_id' => $instituicao->id,
            ]);

            $professor->assignRole('Professor');
        }

        // SuperAdmin
        $superAdmin = User::create([
            'id' => (string) Str::uuid7(),
            'nome' => 'Super Admin',
            'email' => 'mater@sge.ao',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'telefone' => '900000000',
            'instituicao_id' => null,
        ]);

        $superAdmin->assignRole('SuperAdmin');
    }
}
