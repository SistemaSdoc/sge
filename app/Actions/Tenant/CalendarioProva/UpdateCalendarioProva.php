<?php

namespace App\Actions\Tenant\CalendarioProva;

use App\Models\Tenant\CalendarioProva;
use Illuminate\Support\Facades\DB;

class UpdateCalendarioProva
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(CalendarioProva $calendario, array $validated): CalendarioProva
    {
        return DB::transaction(function () use ($calendario, $validated): CalendarioProva {
            $calendario->update([
                'ano_lectivo_id' => $validated['ano_lectivo_id'],
                'curso_classe_turno_id' => $validated['curso_classe_turno_id'],
                'tipo' => $validated['tipo'],
                'trimestre' => CalendarioProva::trimestreForTipo(
                    $validated['tipo'],
                    $validated['trimestre'] ?? null,
                ),
            ]);

            // Substitui todas as entradas
            $calendario->entradas()->delete();

            foreach ($validated['entradas'] as $entrada) {
                $calendario->entradas()->create([
                    'disciplina_id' => $entrada['disciplina_id'],
                    'data' => $entrada['data'],
                    'hora_inicio' => $entrada['hora_inicio'],
                    'hora_fim' => $entrada['hora_fim'],
                ]);
            }

            return $calendario->load('entradas.disciplina');
        });
    }
}
