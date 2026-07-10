<?php

namespace App\Http\Controllers;

use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // $this->authorize('view', $cursoClasse);

        $cursoClasse->load(['classe:id,nome', 'turnos.turno:id,nome']);

        // Turno selecionado (primeiro por defeito)
        $turnoId = request('turno', $cursoClasse->turnos->first()?->id);

        $turnoActual = $cursoClasse->turnos->firstWhere('id', $turnoId);

        $turmas = $turnoActual
            ? $turnoActual->turmas()->withCount('alunosActivos')->orderBy('nome')
                ->paginate(5, ['*'], 'page_turmas')
            : collect();

        $disciplinas = $turnoActual
            ? $turnoActual->classeTurnoDisciplinas()->with('disciplina:id,nome,sigla,componente')
                ->paginate(5, ['*'], 'page_disciplinas')
            : collect();

        return Inertia::render('cursos-tutelados/classes/show', [
            'instituicao' => ['id' => $instituicao->id, 'nome' => $instituicao->nome],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso->curso->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                ],
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'classe' => ['id' => $cursoClasse->classe->id, 'nome' => $cursoClasse->classe->nome],
                'turnos' => $cursoClasse->turnos->map(fn ($t) => ['id' => $t->id, 'nome' => $t->turno->nome])->toArray(),
                'turnoId' => $turnoId,
                'turmas' => $turmas,
                'disciplinas' => $disciplinas,
                'can' => [
                    'create_disciplina' => Auth::user()->can('create', ClasseTurnoDisciplina::class),
                    'create_turma' => Auth::user()->can('create', Turma::class),
                    'create_turno' => Auth::user()->can('create', CursoClasseTurno::class),
                ],
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
