<?php

namespace App\Actions\Tenant\Turno;

use App\Models\Tenant\Turno;

class CreateTurno
{
    /**
     * Cria um turno no tenant actual.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): Turno
    {
        return Turno::create($validated);
    }
}
