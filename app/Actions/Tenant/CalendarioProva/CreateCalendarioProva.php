<?php

namespace App\Actions\Tenant\CalendarioProva;

use App\Models\Tenant\CalendarioProva;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class CreateCalendarioProva
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): CalendarioProva
    {
        return DB::transaction(function () use ($validated): CalendarioProva {
            $calendario = CalendarioProva::create([
                'ano_lectivo_id' => $validated['ano_lectivo_id'],
                'curso_classe_turno_id' => $validated['curso_classe_turno_id'],
                'tipo' => $validated['tipo'],
                'trimestre' => CalendarioProva::trimestreForTipo(
                    $validated['tipo'],
                    $validated['trimestre'] ?? null,
                ),
                'ativo' => false,
            ]);

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
