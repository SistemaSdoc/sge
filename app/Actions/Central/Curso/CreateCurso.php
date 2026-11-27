<?php

namespace App\Actions\Central\Curso;

use App\Models\Central\Curso;

class CreateCurso
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Cria um novo curso na base de dados central.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): Curso
    {
        return Curso::create($validated);
    }
}
