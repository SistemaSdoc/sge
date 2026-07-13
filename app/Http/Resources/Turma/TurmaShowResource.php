<?php

namespace App\Http\Resources\Turma;

use Illuminate\Http\Resources\Json\JsonResource;

class TurmaShowResource extends JsonResource
{
    public function toArray($request): array
    {
        $cct = $this->whenLoaded('cursoClasseTurno', fn () => $this->cursoClasseTurno);

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'max_alunos' => $this->max_alunos,
            'classe' => $cct ? [
                'nome' => $cct->cursoClasse->classe->nome,
            ] : null,
            'can' => [
                'edit' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
