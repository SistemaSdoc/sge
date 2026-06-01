<?php

namespace Database\Seeders;

use App\Models\Instituicao;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            // PERMISSÕES E ROLES
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,

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

        $roles = [
            'Master' => Role::where('nome', 'Master')->first(),
            'Director' => Role::where('nome', 'Director')->first(),
            'Secretaria' => Role::where('nome', 'Secretaria')->first(),
            'Professor' => Role::where('nome', 'Professor')->first(),
            'Aluno' => Role::where('nome', 'Aluno')->first(),
        ];

        $instituicoes = Instituicao::all();

        foreach ($instituicoes as $instituicao) {
            // Master user
            User::create([
                'id'            => (string) Str::uuid7(),
                'name'          => 'Master ' . $instituicao->sigla,
                'email'         => 'master' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password'      => Hash::make('password'),
                'genero'        => 'M',
                'data_nascimento' => '2000-01-01',
                'telefone'      => '923000000',
                'endereco'      => 'Luanda',
                'documento'     => '12345678901',
                'tipo_documento' => 'BI',
                'instituicao_id' => $instituicao->id,
                'status'        => 1,
                'remember_token' => null,
            ]);

            $masterUser = User::where('email', 'master' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao')->first();
            $masterUser->roles()->attach($roles['Master']->id);

            // Director user
            User::create([
                'id'            => (string) Str::uuid7(),
                'name'          => 'Director ' . $instituicao->sigla,
                'email'         => 'director' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password'      => Hash::make('password'),
                'genero'        => 'M',
                'data_nascimento' => '2000-01-01',
                'telefone'      => '923000001',
                'endereco'      => 'Luanda',
                'documento'     => '12345678902',
                'tipo_documento' => 'BI',
                'instituicao_id' => $instituicao->id,
                'status'        => 1,
                'remember_token' => null,
            ]);

            $directorUser = User::where('email', 'director' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao')->first();
            $directorUser->roles()->attach($roles['Director']->id);

            // Secretaria user
            User::create([
                'id'            => (string) Str::uuid7(),
                'name'          => 'Secretaria ' . $instituicao->sigla,
                'email'         => 'secretaria' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password'      => Hash::make('password'),
                'genero'        => 'F',
                'data_nascimento' => '2000-01-01',
                'telefone'      => '923000002',
                'endereco'      => 'Luanda',
                'documento'     => '12345678903',
                'tipo_documento' => 'BI',
                'instituicao_id' => $instituicao->id,
                'status'        => 1,
                'remember_token' => null,
            ]);

            $secretariaUser = User::where('email', 'secretaria' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao')->first();
            $secretariaUser->roles()->attach($roles['Secretaria']->id);

            // Professor user
            User::create([
                'id'            => (string) Str::uuid7(),
                'name'          => 'Professor ' . $instituicao->sigla,
                'email'         => 'professor' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password'      => Hash::make('password'),
                'genero'        => 'M',
                'data_nascimento' => '2000-01-01',
                'telefone'      => '923000003',
                'endereco'      => 'Luanda',
                'documento'     => '12345678904',
                'tipo_documento' => 'BI',
                'instituicao_id' => $instituicao->id,
                'status'        => 1,
                'remember_token' => null,
            ]);

            $professorUser = User::where('email', 'professor' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao')->first();
            $professorUser->roles()->attach($roles['Professor']->id);

            // Aluno user
            User::create([
                'id'            => (string) Str::uuid7(),
                'name'          => 'Aluno ' . $instituicao->sigla,
                'email'         => 'aluno' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao',
                'email_verified_at' => now(),
                'password'      => Hash::make('password'),
                'genero'        => 'M',
                'data_nascimento' => '2005-01-01',
                'telefone'      => '923000004',
                'endereco'      => 'Luanda',
                'documento'     => '12345678905',
                'tipo_documento' => 'BI',
                'instituicao_id' => $instituicao->id,
                'status'        => 1,
                'remember_token' => null,
            ]);

            $alunoUser = User::where('email', 'aluno' . $instituicao->sigla . '@' . $instituicao->sigla . '.ao')->first();
            $alunoUser->roles()->attach($roles['Aluno']->id);
        }
    }
}
