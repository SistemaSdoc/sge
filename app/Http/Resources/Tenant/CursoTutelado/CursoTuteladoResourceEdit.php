<?php

namespace App\Http\Resources\Tenant\CursoTutelado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoTuteladoResourceEdit extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'curso_id' => $this->instituicaoCurso->curso_id,
            'curso' => [
                'nome' => $this->instituicaoCurso->curso->nome,
                'duracao_anos' => $this->instituicaoCurso->duracao_anos,
            ],
            'instituicao_tutora' => [
                'id' => $this->instituicaoTutora->id,
                'nome' => $this->instituicaoTutora->nome,
            ],
            'classes' => $this->classes->pluck('id'),
        ];
    }
}
