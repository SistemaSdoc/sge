<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoTuteladoSeeder extends Seeder
{
    public function run(): void
    {
        $instituicaoCursos = DB::table('instituicao_curso')->get();

        foreach ($instituicaoCursos as $item) {

            DB::table('curso_tutelado')->updateOrInsert(
                [
                    'instituicao_curso_id' => $item->id,
                    'instituicao_tutora_id' => $item->instituicao_id,
                ],
                [
                    'id'                    => (string) Str::uuid7(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
