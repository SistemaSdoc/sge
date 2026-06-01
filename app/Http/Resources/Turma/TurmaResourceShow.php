<?php

namespace App\Http\Resources\Turma;

use Illuminate\Http\Resources\Json\JsonResource;

class TurmaResourceShow extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->nome,
            'max_alunos' => $this->max_alunos,
            'classe' => $this->whenLoaded('cursoClasseTurno', fn() => [
                'id'   => $this->cursoClasseTurno->cursoClasse->classe->id,
                'nome' => $this->cursoClasseTurno->cursoClasse->classe->nome,
            ]),
            'turno' => $this->whenLoaded('cursoClasseTurno', fn() => [
                'id'   => $this->cursoClasseTurno->turno->id,
                'nome' => $this->cursoClasseTurno->turno->nome,
            ]),
            'disciplinas' => $this->whenLoaded('cursoClasseTurno', fn() =>
                $this->cursoClasseTurno->classeTurnoDisciplinas->map(fn($ctd) => [
                    'id'   => $ctd->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'curso_classe_turno_id' => $this->cursoClasseTurno->id,
                ])
            ),
            'alunos' => $this->whenLoaded('alunos', fn() =>
                $this->alunos->map(fn($aluno) => [
                    'id'        => $aluno->id,
                    'matricula' => $aluno->matricula,
                    'nome'      => $aluno->inscricao?->candidato?->nome,
                    'email'     => $aluno->user?->email,
                    'telefone'  => $aluno->user?->telefone,
                ])
            ),
            'grupos_pap' => $this->whenLoaded('gruposPap'),
            'can' => [
                'view' => $request->user()?->can('view', $this->resource) ?? false,
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
