<?php

namespace App\Http\Controllers;

use App\Models\CursoClasse;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class CursoClasseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource (Show page via Inertia).
     */
    public function show(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse) // [CORRIGIDO] Adicionado $request como primeiro parâmetro
    {
        $perPage = 5;

        $currentPageDisciplinas = $request->input('page_disciplinas', 1);
        $currentPageTurmas = $request->input('page_turmas', 1);

        $cursoClasse->load([
            'classe:id,nome',
            'cursoTutelado.instituicaoCurso.curso:id,nome',
            'turnos.turno:id,nome',
            'turnos.classeTurnoDisciplinas.disciplina:id,nome,sigla,componente',
            'turnos.turmas' => function ($query) {
                $query->withCount('alunos');
            },
        ]);

        $disciplinasCollection = collect();
        foreach ($cursoClasse->turnos as $turno) {
            foreach ($turno->classeTurnoDisciplinas as $ctd) {
                $disciplinasCollection->push([
                    'id' => $ctd->disciplina->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'componente' => $ctd->disciplina->componente,
                    'turno_id' => $turno->id,
                    'turno_nome' => $turno->turno->nome,
                ]);
            }
        }

        $turmasCollection = collect();
        foreach ($cursoClasse->turnos as $turno) {
            foreach ($turno->turmas as $turma) {
                $turmasCollection->push([
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'alunos_count' => $turma->alunos_count,
                    'turno_id' => $turno->id,
                    'turno_nome' => $turno->turno->nome,
                ]);
            }
        }

        $disciplinas = new LengthAwarePaginator(
            $disciplinasCollection->forPage($currentPageDisciplinas, $perPage)->values(),
            $disciplinasCollection->count(),
            $perPage,
            $currentPageDisciplinas,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $turmas = new LengthAwarePaginator(
            $turmasCollection->forPage($currentPageTurmas, $perPage)->values(),
            $turmasCollection->count(),
            $perPage,
            $currentPageTurmas,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('cursos-tutelados/classes/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso->curso->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                ],
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'classe' => [
                    'id' => $cursoClasse->classe->id,
                    'nome' => $cursoClasse->classe->nome,
                ],
                'turnos' => $cursoClasse->turnos->map(fn ($turno) => [
                    'id' => $turno->id,
                    'nome' => $turno->turno->nome,
                ])->toArray(),
                'disciplinas_paginated' => $disciplinas->toArray(),
                'turmas_paginated' => $turmas->toArray(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}
}
