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

            'turno' => $cct ? [
                'nome' => $cct->turno->nome,
            ] : null,

            'grupos_pap' => $this->whenLoaded('gruposPap', fn () => $this->gruposPap->map(fn ($g) => [
                'id' => $g->id,
                'nome' => $g->nome_grupo,
                'tema' => $g->tema_grupo,
                'status' => $g->status,
                'nota_final' => $g->nota_final,
            ])
            ),

            'can' => [
                'view' => $request->user()?->can('view', $this->resource) ?? false,
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
