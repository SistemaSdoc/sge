<?php

namespace App\Http\Resources\CursoTutelado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class CursoTuteladoResourceShow extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perPage = 5;

        // [ADICIONADO] variáveis de paginação por secção com parâmetros independentes
        $currentPageTurmas = $request->input('page_turmas', 1);
        $currentPageProfessores = $request->input('page_professores', 1);

        // [ADICIONADO] collection de turmas extraída para variável reutilizável
        $turmasCollection = $this->cursoClasses
            ->flatMap(fn ($cc) => $cc->turnos)
            ->flatMap(fn ($cct) => $cct->turmas)
            ->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'max_alunos' => $turma->max_alunos,
                'curso_classe_turno_id' => $turma->cursoClasseTurno->id,
                'classe' => [
                    'id' => $turma->cursoClasseTurno->cursoClasse->id,
                    'nome' => $turma->cursoClasseTurno->cursoClasse->classe->nome,
                ],
                'turno' => [
                    'id' => $turma->cursoClasseTurno->turno->id,
                    'nome' => $turma->cursoClasseTurno->turno->nome,
                ],
            ]);

        // [ADICIONADO] collection de professores extraída para variável reutilizável
        $professoresCollection = $this->professores->map(fn ($prof) => [
            'id' => $prof->id,
            'nome' => $prof->user?->nome,
            'tipo' => $prof->pivot->tipo,
        ]);

        // [ADICIONADO] paginador manual das turmas
        $turmas = new LengthAwarePaginator(
            $turmasCollection->forPage($currentPageTurmas, $perPage)->values(),
            $turmasCollection->count(),
            $perPage,
            $currentPageTurmas,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // [ADICIONADO] paginador manual dos professores
        $professores = new LengthAwarePaginator(
            $professoresCollection->forPage($currentPageProfessores, $perPage)->values(),
            $professoresCollection->count(),
            $perPage,
            $currentPageProfessores,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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
                'turmas' => $turmasCollection->count(),
                'professores' => $professoresCollection->count(),
                'disciplinas' => $this->cursoClasses
                    ->flatMap(fn ($cc) => $cc->turnos)
                    ->flatMap(fn ($cct) => $cct->classeTurnoDisciplinas)
                    ->count(),
            ],

            'classes' => $this->cursoClasses->map(fn ($cc) => [ // [ALTERADO] voltou ao map directo sem paginação
                'id' => $cc->id,
                'nome' => $cc->classe->nome,
                'turnos' => $cc->turnos->map(fn ($cct) => $cct->turno->nome),
            ]),
            'professores' => $professores->toArray(),
            'turmas' => $turmas->toArray(),
        ];
    }
}