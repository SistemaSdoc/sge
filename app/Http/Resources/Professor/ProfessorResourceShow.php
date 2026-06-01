<?php

namespace App\Http\Resources\Professor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessorResourceShow extends JsonResource
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
            'nome' => $this->user->nome,
            'email' => $this->user->email,
            'bi' => $this->user->bi,
            'telefone' => $this->user->telefone,
            'especialidade' => $this->especialidade,
        ];
    }
}
