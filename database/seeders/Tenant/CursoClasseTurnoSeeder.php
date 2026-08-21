<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Turno;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoClasseTurnoSeeder extends Seeder
{
    public function run(): void
    {
        $cursoClasses = DB::table('curso_classe')->get();
        $turnos = Turno::all();

        foreach ($cursoClasses as $cursoClasse) {
            foreach ($turnos as $turno) {

                DB::table('curso_classe_turno')->updateOrInsert(
                    [
                        'curso_classe_id' => $cursoClasse->id,
                        'turno_id' => $turno->id,
                    ],
                    [
                        'id' => (string) Str::uuid7(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
