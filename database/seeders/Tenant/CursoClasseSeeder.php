<?php

namespace Database\Seeders\Tenant;

use App\Models\tenant\Classe;
use App\Models\tenant\CursoTutelado;
use App\Models\tenant\NivelEnsino;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mapaClasseNivel = [
            'Pré-escolar' => 'Primário',
            '1ª' => 'Primário',
            '2ª' => 'Primário',
            '3ª' => 'Primário',
            '4ª' => 'Primário',
            '5ª' => 'Primário',
            '6ª' => 'Primário',
            '7ª' => 'Iº Ciclo',
            '8ª' => 'Iº Ciclo',
            '9ª' => 'Iº Ciclo',
            '10ª' => 'IIº Ciclo',
            '11ª' => 'IIº Ciclo',
            '12ª' => 'IIº Ciclo',
            '13ª' => 'IIº Ciclo',
        ];

        $niveis = NivelEnsino::pluck('id', 'nome');
        $classes = Classe::pluck('id', 'nome');
        $cursos = CursoTutelado::pluck('id');

        $now = now();
        $rows = [];

        foreach ($cursos as $cursoId) {
            foreach ($mapaClasseNivel as $classeNome => $nivelNome) {
                $classeId = $classes[$classeNome] ?? null;
                $nivelId = $niveis[$nivelNome] ?? null;

                if (! $classeId || ! $nivelId) {
                    continue;
                }

                $rows[] = [
                    'id' => (string) Str::uuid7(),
                    'curso_tutelado_id' => $cursoId,
                    'classe_id' => $classeId,
                    'nivel_ensino_id' => $nivelId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert bulk — uma query
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('curso_classe')->insertOrIgnore($chunk);
        }
    }
}
