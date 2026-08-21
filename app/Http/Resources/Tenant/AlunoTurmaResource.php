<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoTurmaResource extends JsonResource
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
            'matricula' => $this->matricula,
            'nome' => $this->inscricao?->candidato?->nome,
            'email' => $this->user?->email,
            'telefone' => $this->user?->telefone,
            'can' => [
                'view' => $request->user()?->can('view', $this->resource) ?? false,
            ],
        ];
    }
}
