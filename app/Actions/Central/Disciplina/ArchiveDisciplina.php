<?php

namespace App\Actions\Central\Disciplina;

use App\Models\Central\Disciplina;

class ArchiveDisciplina
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Arquiva uma disciplina através de soft delete.
     */
    public function handle(Disciplina $disciplina): void
    {
        $disciplina->delete();
    }
}
