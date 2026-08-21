<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\JsonResource;

class TurmaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'max_alunos' => $this->max_alunos,
            'classe' => $this->whenLoaded('cursoClasseTurno', fn () => [
                'id' => $this->cursoClasseTurno->cursoClasse->id,
                'nome' => $this->cursoClasseTurno->cursoClasse->classe->nome,
            ]),
            'turno' => $this->whenLoaded('cursoClasseTurno', fn () => [
                'id' => $this->cursoClasseTurno->id,
                'nome' => $this->cursoClasseTurno->turno->nome,
            ]),
            'disciplinas' => $this->whenLoaded('cursoClasseTurno', function () {
                $user = auth()->user();
                $professorId = $user?->professor?->id;
                $showAll = $user?->isSuperAdmin() || $user?->isDirector();

                $disciplinaIds = $showAll
                    ? $this->cursoClasseTurno->classeTurnoDisciplinas->pluck('id')->all()
                    : $this->turmaDisciplinaProfessor
                        ->where('professor_id', $professorId)
                        ->pluck('classe_turno_disciplina_id')
                        ->unique()
                        ->all();

                return $this->cursoClasseTurno->classeTurnoDisciplinas
                    ->filter(fn ($ctd) => $showAll || in_array($ctd->id, $disciplinaIds, true))
                    ->map(fn ($ctd) => [
                        'id' => $ctd->id,
                        'nome' => $ctd->disciplina->nome,
                        'professor' => $this->turmaDisciplinaProfessor
                            ->when($showAll, fn ($collection) => $collection)
                            ->when(! $showAll, fn ($collection) => $collection->where('professor_id', $professorId))
                            ->firstWhere('classe_turno_disciplina_id', $ctd->id)
                            ?->professor?->user?->only(['id', 'nome']) ?? null,
                    ])
                    ->values();
            }),

            'alunos' => $this->whenLoaded(
                'alunos',
                fn () => $this->alunos->map(fn ($aluno) => [
                    'id' => $aluno->id,
                    'matricula' => $aluno->matricula,
                    'nome' => $aluno->inscricao?->candidato?->nome,
                    'email' => $aluno->user?->email,
                    'telefone' => $aluno->user?->telefone,
                ])
            ),
            'grupos_pap' => $this->whenLoaded('gruposPap'),
        ];
    }
}
