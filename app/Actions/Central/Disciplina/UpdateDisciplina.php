<?php

namespace App\Actions\Central\Disciplina;

use App\Models\Central\Disciplina;

class UpdateDisciplina
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Actualiza os dados de uma disciplina existente.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Disciplina $disciplina, array $validated): void
    {
        $disciplina->update($validated);
    }
}
