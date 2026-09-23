<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->user->nome,
            'user' => [
                'nome' => $this->user->nome,
                'email' => $this->user->email,
                'telefone' => $this->user->telefone,
            ],
            'especialidade' => $this->especialidade,

            // Expõe o id do pivot quando carregado via belongsToMany
            'vinculo_id' => $this->whenPivotLoaded('curso_tutelado_professor', fn() => $this->pivot->id),
            'tipo' => $this->whenPivotLoaded('curso_tutelado_professor', fn() => $this->pivot->tipo),

            'turnos' => $this->whenLoaded(
                'turmaDisciplinaProfessor',
                fn() => $this->turmaDisciplinaProfessor
                    ->filter(fn($tdp) => $tdp->classeTurnoDisciplina?->cursoClasseTurno?->turno)
                    ->map(fn($tdp) => [
                        'id' => $tdp->classeTurnoDisciplina->cursoClasseTurno->turno->id,
                        'nome' => $tdp->classeTurnoDisciplina->cursoClasseTurno->turno->nome,
                    ])->values()
            ),
            'turmas' => $this->whenLoaded('turmas'),
        ];
    }
}
