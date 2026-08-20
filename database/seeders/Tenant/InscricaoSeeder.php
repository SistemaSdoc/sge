<?php

namespace Database\Seeders\Tenant;

use App\Models\tenant\AnoLectivo;
use App\Models\tenant\Candidato;
use App\Models\tenant\CursoClasseTurno;
use App\Models\tenant\Inscricao;
use Illuminate\Database\Seeder;

class InscricaoSeeder extends Seeder
{
    public function run(): void
    {
        $candidatos = Candidato::doesntHave('inscricoes')->get();

        if ($candidatos->isEmpty()) {
            $this->command->warn('Nenhum candidato sem inscrição encontrado. Executa o CandidatoSeeder primeiro.');

            return;
        }

        $anoLectivo = AnoLectivo::activo()->id ?? AnoLectivo::latest()->first();
        $cursoClasseTurnos = CursoClasseTurno::all();

        if (! $anoLectivo || $cursoClasseTurnos->isEmpty()) {
            $this->command->warn('É preciso ter pelo menos um AnoLectivo e um CursoClasseTurno cadastrados.');

            return;
        }

        $statusPossiveis = ['pendente', 'aprovado', 'reprovado', 'cancelado'];

        foreach ($candidatos as $candidato) {
            Inscricao::create([
                'curso_classe_turno_id' => $cursoClasseTurnos->random()->id,
                'candidato_id' => $candidato->id,
                'ano_lectivo_id' => $anoLectivo->id,
                'status' => fake()->randomElement($statusPossiveis),
                'nota_teste' => fake()->randomFloat(2, 0, 20),
            ]);
        }
    }
}
