<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aluno_id' => $this->aluno->id,
            'nome' => $this->aluno?->inscricao?->candidato?->nome,
            'email' => $this->aluno?->inscricao?->candidato?->email,
            'matricula' => $this->aluno?->matricula,
            'nota_individual' => $this->nota_individual,
        ];
    }
}
