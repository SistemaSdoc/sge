<?php

namespace App\Http\Resources\Turma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TurmaResourceIndex extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'instituicao' => [
                'id' => $this->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->instituicao->id,
            ],
            'curso' => [
                'id' => $this->cursoClasseTurno->cursoClasse->cursoTutelado->id,
                'nome' => $this->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'classe' => [
                'id' => $this->cursoClasseTurno->cursoClasse->id,
                'nome' => $this->cursoClasseTurno->cursoClasse->classe->nome,
            ],
            'ano_lectivo' => $this->anoLectivo?->nome,
            'turno' => [
                'id' => $this->cursoClasseTurno->id,
                'nome' => $this->cursoClasseTurno->turno->nome,
            ],
            'total_alunos' => $this->alunosActivos->count(),
            'can' => [
                'view' => $user?->can('view', $this->resource) ?? false,
                'update' => $user?->can('update', $this->resource) ?? false,
                'delete' => $user?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
