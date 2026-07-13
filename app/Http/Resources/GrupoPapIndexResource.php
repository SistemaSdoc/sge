<?php

namespace App\Http\Resources;

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
            'nome' => $this->nome_grupo,
            'tema' => $this->tema_grupo,
            'status' => $this->status,
            'nota_final' => $this->nota_final,
            'can' => [
                'view' => $request->user()?->can('view', $this->resource) ?? false,
            ],
        ];
    }
}
