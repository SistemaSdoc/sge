<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'professores' => collect($this->professores)->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome,
            ])->values(),
            'alunos' => collect($this->alunos)->map(fn ($aluno) => [
                'id' => $aluno['id'],
                'nome' => $aluno['nome'],
            ])->values(),
            'grupoPap' => [
                'id' => $this->grupoPap->id,
                'nome_grupo' => $this->grupoPap->nome_grupo,
                'tema_grupo' => $this->grupoPap->tema_grupo,
                'estudo_caso' => $this->grupoPap->estudo_caso,
                'status' => $this->grupoPap->status,
                'nota_final' => $this->grupoPap->nota_final,
                'data_defesa' => $this->grupoPap->data_defesa,
                'professor_tutor_id' => $this->grupoPap->professor_tutor_id,
                'alunos' => $this->grupoPap->alunos->pluck('id')->values(),
            ],
        ];
    }
}
