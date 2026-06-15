<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreateResource extends JsonResource
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
        ];
    }
}
