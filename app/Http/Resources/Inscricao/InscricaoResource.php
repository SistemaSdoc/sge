<?php

namespace App\Http\Resources\Inscricao;

use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'candidato' => $this->candidato->nome,
            'curso' => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno' => $this->cursoClasseTurno?->turno?->nome,
            'can' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'cancelar' => $user->can('cancelar', $this->resource),
                'reativar' => $user->can('reativar', $this->resource),
            ],
        ];
    }
}
