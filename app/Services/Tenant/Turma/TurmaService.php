<?php

namespace App\Services\Tenant\Turma;

use App\Models\tenant\Turma;
use Illuminate\Validation\ValidationException;

class TurmaService
{
    /**
     * Cria uma nova turma garantindo unicidade por turno e ano lectivo.
     */
    public function criar(
        string $cursoClasseTurnoId,
        string $anoLectivoId,
        string $nome,
        ?int $maxAlunos = null
    ): Turma {
        $jaExiste = Turma::where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->where('nome', $nome)
            ->exists();

        if ($jaExiste) {
            throw ValidationException::withMessages([
                'nome' => 'Já existe uma turma com este nome neste turno para o ano lectivo seleccionado.',
            ]);
        }

        return Turma::create([
            'curso_classe_turno_id' => $cursoClasseTurnoId,
            'ano_lectivo_id' => $anoLectivoId,
            'nome' => $nome,
            'max_alunos' => $maxAlunos,
        ]);
    }
}
