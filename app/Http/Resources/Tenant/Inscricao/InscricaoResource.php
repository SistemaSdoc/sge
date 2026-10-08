<?php

namespace App\Http\Resources\Tenant\Inscricao;

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
        $alunoRemovido = $this->aluno?->trashed() ?? false;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'candidato' => $alunoRemovido ? 'Aluno removido' : ($this->candidato?->nome ?? 'Aluno removido'),
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
