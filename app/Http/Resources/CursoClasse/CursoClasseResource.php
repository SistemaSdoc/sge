<?php

namespace App\Http\Resources\CursoClasse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoClasseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $curso = $this->cursoTutelado?->instituicaoCurso?->curso;

        return [
            'id' => $this->id,
            'classe' => [
                'id' => $this->classe?->id,
                'nome' => $this->classe?->nome,
            ],
            'curso' => [
                'id' => $curso?->id,
                'nome' => $curso?->nome,
            ],
            'turnos' => $this->turnos->map(fn ($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno?->nome,
                'turmas' => $cct->turmas->map(fn ($turma) => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'max_alunos' => $turma->max_alunos,
                    'alunos_count' => $turma->alunos()->count(),
                ]),
                'disciplinas' => $cct->classeTurnoDisciplinas->map(fn ($ctd) => [
                    'id' => $ctd->id,
                    'disciplina_id' => $ctd->disciplina?->id,
                    'nome' => $ctd->disciplina?->nome,
                    'sigla' => $ctd->disciplina?->sigla,
                    'componente' => $ctd->disciplina?->componente,
                    'carga_horaria' => $ctd->carga_horaria,
                    'tem_professor' => $ctd->tem_professor,
                ]),
            ]),
        ];
    }
}
