<?php

namespace App\Actions\Central\Disciplina;

use App\Models\Central\Disciplina;

class RestoreDisciplina
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Restaura uma disciplina arquivada.
     */
    public function handle(Disciplina $disciplina): void
    {
        $disciplina->restore();
    }
}
