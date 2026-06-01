<?php

namespace App\Http\Resources\Inscricao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoResourceIndex extends JsonResource
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
            'nome_candidato' => $this->candidato->nome,
            'curso' => $this->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
            'status' => $this->status,
        ];
    }
}
