<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TurmaSeeder extends Seeder
{
    public function run(): void
    {
        $anoLectivoId = DB::table('ano_lectivos')
            ->where('activo', true)
            ->value('id');

        if (! $anoLectivoId) {
            $this->command->error('Nenhum ano lectivo activo encontrado. Corre o AnoLectivoSeeder primeiro.');

            return;
        }

        $cursoClasseTurnos = DB::table('curso_classe_turno')
            ->join(
                'curso_classe',
                'curso_classe.id',
                '=',
                'curso_classe_turno.curso_classe_id'
            )
            ->join(
                'curso_tutelado',
                'curso_tutelado.id',
                '=',
                'curso_classe.curso_tutelado_id'
            )
            ->join(
                'instituicao_curso',
                'instituicao_curso.id',
                '=',
                'curso_tutelado.instituicao_curso_id'
            )
            ->join(
                'cursos',
                'cursos.id',
                '=',
                'instituicao_curso.curso_id'
            )
            ->join(
                'turnos',
                'turnos.id',
                '=',
                'curso_classe_turno.turno_id'
            )
            ->where('cursos.nome', 'Informática de Gestão')
            ->select(
                'curso_classe_turno.id',
                'cursos.nome as curso',
                'turnos.nome as turno'
            )
            ->get();

        foreach ($cursoClasseTurnos as $item) {

            $siglaCurso = 'I';

            $siglaTurno = match ($item->turno) {
                'Manhã' => 'M',
                'Tarde' => 'T',
                'Noite' => 'N',
                default => 'X'
            };

            $nomeTurma = 'A'.$siglaTurno.$siglaCurso;

            DB::table('turmas')->updateOrInsert(
                [
                    'curso_classe_turno_id' => $item->id,
                ],
                [
                    'id' => (string) Str::uuid7(),
                    'ano_lectivo_id' => $anoLectivoId,
                    'nome' => $nomeTurma,
                    'max_alunos' => 35,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
