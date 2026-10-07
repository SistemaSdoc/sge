<?php

namespace App\Actions\Tenant\Turno;

use App\Models\Tenant\Turno;

class UpdateTurno
{
    /**
     * Actualiza um turno no tenant actual.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Turno $turno, array $validated): void
    {
        $turno->update($validated);
    }
}
