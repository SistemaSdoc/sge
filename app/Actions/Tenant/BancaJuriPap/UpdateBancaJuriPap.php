<?php

namespace App\Actions\Tenant\BancaJuriPap;

use App\Models\Tenant\BancaJuriPap;

class UpdateBancaJuriPap
{
    /**
     * Actualiza o professor e a função de um jurado da banca.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(BancaJuriPap $bancaJuriPap, array $validated): void
    {
        $bancaJuriPap->update([
            'professor_id' => $validated['professor_id'],
            'funcao' => $validated['funcao'],
        ]);
    }
}
