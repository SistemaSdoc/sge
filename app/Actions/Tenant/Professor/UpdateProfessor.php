<?php

namespace App\Actions\Tenant\Professor;

use App\Models\Tenant\Professor;
use Illuminate\Support\Facades\DB;

class UpdateProfessor
{
    /**
     * Actualiza os dados pessoais e académicos de um professor.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Professor $professor, array $validated): void
    {
        DB::transaction(function () use ($professor, $validated): void {
            $professor->user()->update([
                'nome' => $validated['nome'] ?? null,
                'email' => $validated['email'] ?? null,
                'bi' => $validated['bi'] ?? null,
                'telefone' => $validated['telefone'] ?? null,
            ]);

            $professor->update([
                'especialidade' => $validated['especialidade'] ?? null,
                'nivel_academico' => $validated['nivel_academico'] ?? null,
            ]);
        });
    }
}
