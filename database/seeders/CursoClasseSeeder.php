<?php

namespace Database\Seeders;

use App\Models\Classe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoClasseSeeder extends Seeder
{
    public function run(): void
    {
        $cursoTutelados = DB::table('curso_tutelado')->get();
        $classes = Classe::all();

        foreach ($cursoTutelados as $cursoTutelado) {
            foreach ($classes as $classe) {

                DB::table('curso_classe')->updateOrInsert(
                    [
                        'curso_tutelado_id' => $cursoTutelado->id,
                        'classe_id' => $classe->id,
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
}
