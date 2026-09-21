<?php

namespace App\Actions\Tenant\Turno;

use App\Models\Tenant\Turno;

class DeleteTurno
{
    /**
     * Remove um turno do tenant actual.
     */
    public function handle(Turno $turno): void
    {
        $turno->delete();
    }
}
