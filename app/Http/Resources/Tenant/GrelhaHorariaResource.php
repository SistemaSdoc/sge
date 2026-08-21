<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrelhaHorariaResource extends JsonResource
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
            'sigla' => $this->resource['sigla'] ?? $this->sigla,
            'disciplina' => $this->resource['disciplina'] ?? $this->disciplina,
            'professor' => $this->resource['professor'] ?? $this->professor,
        ];
    }
}
