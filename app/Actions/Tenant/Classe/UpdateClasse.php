<?php

namespace App\Actions\Tenant\Classe;

use App\Models\Tenant\Classe;

class UpdateClasse
{
    /**
     * Actualiza uma classe no tenant actual.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Classe $classe, array $validated): void
    {
        $classe->update($validated);
    }
}
