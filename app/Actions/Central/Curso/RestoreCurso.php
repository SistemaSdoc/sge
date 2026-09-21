<?php

namespace App\Actions\Central\Curso;

use App\Models\Central\Curso;

class RestoreCurso
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Restaura um curso arquivado.
     */
    public function handle(Curso $curso): void
    {
        $curso->restore();
    }
}
