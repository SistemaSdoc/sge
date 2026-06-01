<?php

namespace App\Http\Resources\Curso;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoResourceIndex extends JsonResource
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
            // 'duracao_anos' => $this->duracao_anos,
            //'descricao' => $this->descricao,
            //'status' => $this->status,
        ];
    }
}
