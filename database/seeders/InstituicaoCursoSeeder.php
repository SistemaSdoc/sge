<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstituicaoCursoSeeder extends Seeder
{
    public function run(): void
    {
        $instituicoes = DB::table('instituicoes')->get();
        $cursos = DB::table('cursos')->get();

        foreach ($instituicoes as $instituicao) {
            foreach ($cursos as $curso) {
                $exists = DB::table('instituicao_curso')
                    ->where('instituicao_id', $instituicao->id)
                    ->where('curso_id', $curso->id)
                    ->exists();

                if (! $exists) {
                    DB::table('instituicao_curso')->insert([
                        'id'                    => (string) Str::uuid7(),
                        'instituicao_id' => $instituicao->id,
                        'curso_id' => $curso->id,
                        'duracao_anos' => 4,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
