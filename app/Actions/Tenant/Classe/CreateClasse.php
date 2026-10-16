<?php

namespace App\Actions\Tenant\Classe;

use App\Models\Tenant\Classe;

class CreateClasse
{
    /**
     * Cria uma classe no tenant actual.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): Classe
    {
        return Classe::create($validated);
    }
}
