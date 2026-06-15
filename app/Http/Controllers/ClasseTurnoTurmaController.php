<?php

namespace App\Http\Controllers;

use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
<<<<<<< HEAD
use Inertia\Inertia; // [ADICIONADO]
=======
use Inertia\Inertia;
use App\Services\PautaService;


>>>>>>> 286c962cb1809e4c27033fdaa57b8131db79dde7

class ClasseTurnoTurmaController extends Controller /* implements HasMiddleware */
{

    public function __construct(
        private readonly PautaService $pautaService,
    ) {
    }
    /* public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index',  only: ['index']),
            new Middleware('permission:turmas.create', only: ['store']),
            new Middleware('permission:turmas.edit',   only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy']),
        ];
    } */

    /**
     * Display a listing of the resource.
     */

    public function index(CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $turmas = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)->paginate(5);

        return response()->json(
            $turmas->through(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'max_alunos' => $turma->max_alunos,
                'alunos_count' => $turma->alunos()->count(),
            ])
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        return Inertia::render('cursos-tutelados/classes/turnos/turmas/create', [
            'instituicao' => [
                'id' => $instituicao->id,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        $jaExiste = Turma::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->where('nome', $request->nome)
            ->exists();

        if ($jaExiste) {
            return back()->withErrors(['nome' => 'Já existe uma turma com este nome neste turno.']);
        }

        Turma::create([
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'nome' => $request->nome,
            'max_alunos' => $request->max_alunos,
        ]);

        return redirect()->to("/instituicoes/{$instituicao->id}/cursos-tutelados/{$cursoTutelado->id}/classes/{$cursoClasse->id}")
            ->with('toast', [
                'type' => 'success',
                'message' => 'Turma criada com sucesso!',
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma) // [MODIFICADO] Adicionado Request $request
    {
        $perPage = 5; // [ADICIONADO] itens por página

        // [ADICIONADO] variáveis de paginação independentes
        $currentPageAlunos = $request->input('page_alunos', 1);
        $currentPageProfessores = $request->input('page_professores', 1);
        $currentPageGrupos = $request->input('page_grupos', 1);

        // Carregar os relacionamentos sem paginação para ter a collection completa
        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina:id,nome,sigla',
            'alunos' => fn ($q) => $q->wherePivot('activo', true)
                ->with(['inscricao.candidato:id,nome', 'user:id,email,telefone']),
            'turmaDisciplinaProfessor.professor.user:id,nome,email',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            'gruposPap:id,turma_id,nome_grupo,tema_grupo,status,nota_final',
        ]);

<<<<<<< HEAD
        // [ADICIONADO] extrair alunos para collection paginável
        $alunosCollection = collect();
        foreach ($turma->alunos as $aluno) {
            $alunosCollection->push([
                'id' => $aluno->id,
                'nome' => $aluno->nome,
                'email' => $aluno->user?->email,
                'telefone' => $aluno->user?->telefone,
                'inscricao' => $aluno->inscricao ? [
                    'candidato' => [
                        'id' => $aluno->inscricao->candidato->id,
                        'nome' => $aluno->inscricao->candidato->nome,
                    ],
                ] : null,
            ]);
        }

        // [ADICIONADO] extrair professores para collection paginável
        $professoresCollection = collect();
        foreach ($turma->turmaDisciplinaProfessor as $tdp) {
            $professoresCollection->push([
                'id' => $tdp->id,
                'professor' => [
                    'id' => $tdp->professor->id,
                    'nome' => $tdp->professor->user?->nome,
                    'email' => $tdp->professor->user?->email,
                ],
                'disciplina' => [
                    'id' => $tdp->classeTurnoDisciplina->disciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                ],
            ]);
        }

        // [ADICIONADO] extrair grupos PAP para collection paginável
        $gruposCollection = collect();
        foreach ($turma->gruposPap as $grupo) {
            $gruposCollection->push([
                'id' => $grupo->id,
                'nome_grupo' => $grupo->nome_grupo,
                'tema_grupo' => $grupo->tema_grupo,
                'status' => $grupo->status,
                'nota_final' => $grupo->nota_final,
            ]);
        }

        // [ADICIONADO] paginador manual dos alunos
        $alunos = new LengthAwarePaginator(
            $alunosCollection->forPage($currentPageAlunos, $perPage)->values(),
            $alunosCollection->count(),
            $perPage,
            $currentPageAlunos,
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

        // [ADICIONADO] paginador manual dos grupos PAP
        $grupos = new LengthAwarePaginator(
            $gruposCollection->forPage($currentPageGrupos, $perPage)->values(),
            $gruposCollection->count(),
            $perPage,
            $currentPageGrupos,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'classe' => [
                    'id' => $cursoClasse->classe->id,
                    'nome' => $cursoClasse->classe->nome,
                ],
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'turno' => [
                    'id' => $cursoClasseTurno->turno->id,
                    'nome' => $cursoClasseTurno->turno->nome,
                ],
                'disciplinas' => $cursoClasseTurno->classeTurnoDisciplinas->map(fn ($ctd) => [
                    'id' => $ctd->disciplina->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'tem_professor' => $ctd->tem_professor,
                ]),
            ],
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'max_alunos' => $turma->max_alunos,
                'created_at' => $turma->created_at,
                'updated_at' => $turma->updated_at,
            ],
            'alunos_paginated' => $alunos->toArray(),
            'professores_paginated' => $professores->toArray(),
            'grupos_paginated' => $grupos->toArray(),
=======
        $pautaRecurso = $this->pautaService->gerarPautaRecurso($turma);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/show', [
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'cursoClasseTurno' => $cursoClasseTurno,
            'turma' => $turma,
            'pautaRecurso' => $pautaRecurso,
>>>>>>> 286c962cb1809e4c27033fdaa57b8131db79dde7
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'max_alunos' => 'nullable|integer|min:1',
        ]);

        $turma->update($request->only(['nome', 'max_alunos']));

        return response()->json(status: 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        abort_if($turma->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        $temAlunos = $turma->alunos()->exists();

        if ($temAlunos) {
            return response()->json([
                'message' => 'Não é possível remover uma turma que tem alunos associados.',
            ], 422);
        }

        $turma->delete();

        return response()->json(status: 200);
    }
}
