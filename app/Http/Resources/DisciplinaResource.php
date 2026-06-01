<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisciplinaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'classes' => $this->classes->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'nome' => $classe->classe?->nome,
                ];
            }),
        ];
    }
}
