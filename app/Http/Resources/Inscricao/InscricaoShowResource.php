<?php

namespace App\Http\Resources\Inscricao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'nota_teste' => $this->nota_teste,
            'ano_lectivo' => $this->anoLectivo?->nome,
            'candidato'  => [
                'nome'             => $this->candidato?->nome,
                'bi'               => $this->candidato?->bi,
                'numero_estudante' => $this->candidato?->numero_estudante,
                'email'            => $this->candidato?->email,
                'telefone'         => $this->candidato?->telefone,
                'morada'           => $this->candidato?->morada,
            ],
            'curso'      => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao'=> $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno'      => $this->cursoClasseTurno?->turno?->nome,
        ];
    }
}
