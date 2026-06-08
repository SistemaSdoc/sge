<?php

namespace App\Http\Resources\CursoTutelado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoTuteladoResourceShow extends JsonResource
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
            'curso' => [
                'nome' => $this->instituicaoCurso->curso->nome,
                'descricao' => $this->instituicaoCurso->curso->descricao,
                'duracao_anos' => $this->instituicaoCurso->duracao_anos,
            ],
            'instituicao' => [
                'id' => $this->instituicaoCurso->instituicao->id,
                'nome' => $this->instituicaoCurso->instituicao->nome,
            ],
            'instituicao_tutora' => [
                'id' => $this->instituicaoTutora->id,
                'nome' => $this->instituicaoTutora->nome,
            ],

            'contadores' => [
                'turmas' => $this->cursoClasses
                    ->flatMap(fn ($cc) => $cc->turnos)
                    ->flatMap(fn ($cct) => $cct->turmas)
                    ->count(),
                'professores' => $this->cursoClasses
                    ->flatMap(fn ($cc) => $cc->turnos)
                    ->flatMap(fn ($cct) => $cct->classeTurnoDisciplinas)
                    ->flatMap(fn ($ctd) => $ctd->professores->pluck('id'))
                    ->unique()
                    ->count(),
                'disciplinas' => $this->cursoClasses
                    ->flatMap(fn ($cc) => $cc->turnos)
                    ->flatMap(fn ($cct) => $cct->classeTurnoDisciplinas)
                    ->count(),
            ],
            
            'classes' => $this->cursoClasses->map(fn ($cc) => [
                'id' => $cc->id,
                'nome' => $cc->classe->nome,
                'turnos' => $cc->turnos->map(fn ($cct) => $cct->turno->nome),
            ]),
            'turmas' => $this->cursoClasses
                ->flatMap(fn ($cc) => $cc->turnos)
                ->flatMap(fn ($cct) => $cct->turmas)
                ->map(fn ($turma) => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'max_alunos' => $turma->max_alunos,
                    'classe' => [
                        'id' => $turma->cursoClasseTurno->cursoClasse->classe->id,
                        'nome' => $turma->cursoClasseTurno->cursoClasse->classe->nome,
                    ],
                    'turno' => [
                        'id' => $turma->cursoClasseTurno->turno->id,
                        'nome' => $turma->cursoClasseTurno->turno->nome,
                    ],
                ]),
        ];
    }
}
