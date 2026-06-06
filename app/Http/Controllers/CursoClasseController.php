<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CursoClasse;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
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
    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse)
    {
        $cursoClasse->load([
            'classe:id,nome',
            'cursoTutelado.instituicaoCurso.curso:id,nome',
            'turnos.turno:id,nome',
            'turnos.classeTurnoDisciplinas.disciplina:id,nome,sigla,componente',
            'turnos.turmas' => function ($query) {
                $query->withCount('alunos');
            },
        ]);

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
                    'disciplinas' => $turno->classeTurnoDisciplinas->map(fn ($ctd) => [
                        'id' => $ctd->disciplina->id,
                        'nome' => $ctd->disciplina->nome,
                        'sigla' => $ctd->disciplina->sigla,
                        'componente' => $ctd->disciplina->componente,
                    ])->toArray(),
                    'turmas' => $turno->turmas->map(fn ($turma) => [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                        'alunos_count' => $turma->alunos_count,
                    ])->toArray(),
                ])->toArray(),
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
