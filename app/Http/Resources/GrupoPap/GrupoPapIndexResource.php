<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_grupo' => $this->nome_grupo,
            'tema_grupo' => $this->tema_grupo,
            'turma' => $this->turma->nome,
            'status' => $this->status,
            'professor' => [
                'id' => $this->professor->id,
                'nome' => $this->professor->user->nome,
            ],
        ];
    }
}
