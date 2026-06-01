<?php

namespace App\Http\Resources\Inscricao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoResourceShow extends JsonResource
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
            'status' => $this->status,
            'candidato' => [
                'id' => $this->candidato->id,
                'nome' => $this->candidato->nome,
                'nota_teste' => $this->inscricao->nota_teste,
                'numero_estudante' => $this->candidato->numero_estudante,
                'morada' => $this->candidato->morada,
                'email' => $this->candidato->user->email,
                'bi' => $this->candidato->user->bi,
                'telefone' => $this->candidato->user->telefone,
            ],
            'curso' => $this->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
            'classe' => $this->cursoClasseTurno->cursoClasse->classe->nome,
            'instituicao' => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno' => $this->cursoClasseTurno->turno->nome,
            'created_at' => $this->created_at
        ];
    }
}
