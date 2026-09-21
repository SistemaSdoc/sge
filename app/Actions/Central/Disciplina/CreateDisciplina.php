<?php

namespace App\Actions\Central\Disciplina;

use App\Models\Central\Disciplina;

class CreateDisciplina
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Cria uma nova disciplina na base de dados central.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): Disciplina
    {
        return Disciplina::create($validated);
    }
}
