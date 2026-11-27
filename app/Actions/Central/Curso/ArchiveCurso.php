<?php

namespace App\Actions\Central\Curso;

use App\Models\Central\Curso;

class ArchiveCurso
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Arquiva um curso através de soft delete.
     */
    public function handle(Curso $curso): void
    {
        $curso->delete();
    }
}
