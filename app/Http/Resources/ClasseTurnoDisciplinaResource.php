<?php

namespace App\Http\Resources;

use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseTurnoDisciplinaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tdp = $this->turmaDisciplinaProfessores->first();

        return [
            'id' => $this->id,
            'nome' => $this->disciplina?->nome,
            'sigla' => $this->disciplina?->sigla,
            'professor' => $tdp?->professor?->user?->nome
                ? [
                    'nome' => $tdp->professor->user->nome,
                ]
                : null,
            'horarios' => $this->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => $h->hora_inicio->format('H:i'),
                'hora_fim' => $h->hora_fim->format('H:i'),
            ]),
            'can' => [
                'view' => $request->user()?->can('view', $tdp),
                'assign_professor' => $request->user()?->can('create', TurmaDisciplinaProfessor::class),
            ],
        ];
    }
}
