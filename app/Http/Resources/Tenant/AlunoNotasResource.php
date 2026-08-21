<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoNotasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? $this->id,
            'disciplina' => $this->resource['disciplina'] ?? $this->disciplina,
            'sigla' => $this->resource['sigla'] ?? $this->sigla,
            'trimestres' => $this->resource['trimestres'] ?? [],
            'total_faltas' => $this->resource['total_faltas'] ?? $this->faltas,
            'mediaFinal' => $this->resource['mediaFinal'] ?? $this->media_final,
            'status' => $this->resource['status'] ?? $this->situacao_anual,
        ];
    }
}
