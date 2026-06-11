<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_grupo' => $this->nome_grupo,
            'tema_grupo' => $this->tema_grupo,
            'estudo_caso' => $this->estudo_caso,
            'status' => $this->status,
            'nota_final' => $this->nota_final,
            'data_defesa' => $this->data_defesa,
            'local_defesa' => $this->local_defesa,
            'professor' => $this->professor ? [
                'id' => $this->professor->id,
                'nome' => $this->professor->user->nome,
                'email' => $this->professor->user->email,
            ] : null,
            'elementos' => $this->elementos->map(fn ($el) => [
                'id' => $el->id,
                'aluno_id' => $el->aluno->id,
                'nome' => $el->aluno?->inscricao?->candidato?->nome,
                'email' => $el->aluno?->inscricao?->candidato?->email,
                'matricula' => $el->aluno?->matricula,
                'nota_individual' => $el->nota_individual,
            ]),
            'banca' => $this->jurados->map(fn ($j) => [
                'id' => $j->id,
                'professor_id' => $j->professor->id,
                'nome' => $j->professor?->user->nome,
                'email' => $j->professor?->user->email,
                'funcao' => $j->funcao,
            ]),
        ];
    }
}
