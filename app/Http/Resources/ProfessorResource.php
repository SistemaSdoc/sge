<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->user->nome,  // ← directo para o select
            'user' => [
                'nome' => $this->user->nome,
                'email' => $this->user->email,
                'telefone' => $this->user->telefone,

            ],
            'especialidade' => $this->especialidade,
            'turnos' => $this->whenLoaded(
                'turmaDisciplinaProfessor',
                fn () => $this->turmaDisciplinaProfessor
                    ->filter(fn ($tdp) => $tdp->classeTurnoDisciplina?->cursoClasseTurno?->turno) // ← evita null
                    ->map(fn ($tdp) => [
                        'id' => $tdp->classeTurnoDisciplina->cursoClasseTurno->turno->id,
                        'nome' => $tdp->classeTurnoDisciplina->cursoClasseTurno->turno->nome,
                    ])->values()
            ),
            'turmas' => $this->whenLoaded('turmas'),
        ];
    }
}
