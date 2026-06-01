<?php

namespace App\Http\Resources\Instituicao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstituicaoResourceIndex extends JsonResource
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
            'nome' => $this->nome,
            'sigla' => $this->sigla,
            'tipo' => $this->tipo,
        ];
    }
}
