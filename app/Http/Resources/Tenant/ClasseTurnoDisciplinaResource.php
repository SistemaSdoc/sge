<?php

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\TurmaDisciplinaProfessor;
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
        $tdp = $this->turmaDisciplinaProfessores
            ->first(fn(TurmaDisciplinaProfessor $tdp) => $tdp->professor_id !== null);

        return [
            'id' => $this->id,
            'nome' => $this->disciplina?->nome ?? 'Disciplina arquivada',
            'sigla' => $this->disciplina?->sigla ?? '—',
            'arquivada' => $this->disciplina === null || $this->disciplina->deleted_at !== null,
            'professor' => $tdp?->professor?->user?->nome
                ? [
                    'nome' => $tdp->professor->user->nome,
                ]
                : null,
            'horarios' => $this->horarios->map(fn($h) => [
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => $h->hora_inicio->format('H:i'),
                'hora_fim' => $h->hora_fim->format('H:i'),
            ]),
            'can' => [
                'view' => $tdp && $request->user()?->can('view', $tdp),
                'assign_professor' => $request->user()?->can('definirProfessor', new TurmaDisciplinaProfessor),
                'delete' => $request->user()?->can('delete', $this->resource),
                'detach_professor' => $tdp
                    && !$tdp->temHistorico()
                    && $request->user()?->can('delete', $tdp),
                'manage_schedule' => $request->user()?->can('create', new ClasseTurnoDisciplinaHorario),
            ],
        ];
    }
}
