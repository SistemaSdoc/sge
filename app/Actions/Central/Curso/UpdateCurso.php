<?php

namespace App\Actions\Central\Curso;

use App\Models\Central\Curso;

class UpdateCurso
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Actualiza os dados de um curso existente.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Curso $curso, array $validated): void
    {
        $curso->update($validated);
    }
}
