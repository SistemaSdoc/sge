<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapResourceShow extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nome_grupo'     => $this->nome_grupo,
            'tema_grupo'     => $this->tema_grupo,
            'estudo_caso'    => $this->estudo_caso,
            'trabalho_grupo' => $this->trabalho_grupo,
            'status'         => $this->status,
            'nota_final'     => $this->nota_final,
            'data_defesa'    => $this->data_defesa,
            'local_defesa'   => $this->local_defesa,
            'turma' => [
                'id'   => $this->turma->id,
                'nome' => $this->turma->nome,
            ],
            'professor_tutor' => [
                'id'   => $this->professor->id,
                'nome' => $this->professor->user->nome,
            ],
            'elementos' => $this->elementos->map(fn($elemento) => [
                'id'              => $elemento->aluno->id,
                'nome'            => $elemento->aluno->user->nome,
                'nota_individual' => $elemento->nota_individual,
            ]),
        ];
    }
}
