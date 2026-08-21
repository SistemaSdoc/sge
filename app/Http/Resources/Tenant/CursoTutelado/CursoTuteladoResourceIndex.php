<?php

namespace App\Http\Resources\Tenant\CursoTutelado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoTuteladoResourceIndex extends JsonResource
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
            'nome' => $this->instituicaoCurso->curso->nome,
            'instituicao_tutora' => $this->instituicaoTutora->nome,
        ];
    }
}
