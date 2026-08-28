<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza os dados e os elementos de um grupo PAP.
 */
class UpdateGrupoPap
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(GrupoPap $grupoPap, array $validated): void
    {
        DB::transaction(function () use ($grupoPap, $validated): void {
            $grupoPap->update(array_intersect_key($validated, array_flip([
                'nome_grupo',
                'tema_grupo',
                'estudo_caso',
                'status',
                'nota_final',
                'data_defesa',
                'professor_tutor_id',
            ])));

            if (array_key_exists('alunos', $validated)) {
                $grupoPap->alunos()->sync($validated['alunos']);
            }
        });
    }
}
