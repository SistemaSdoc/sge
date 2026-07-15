<?php

namespace App\Http\Controllers\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicaoCurso\StoreProfessorRequest;
use App\Models\AnoLectivo;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TurmaDisciplinaProfessorController extends Controller
{
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        Gate::authorize('definirProfessor', new TurmaDisciplinaProfessor);

        $classeTurnoDisciplina->load('disciplina');

        $professores = $cursoTutelado->professores()
            ->with('user:id,nome')
            ->get()
            ->map(fn(Professor $professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ]);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/professores/create', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
            'professores' => $professores,
            'disciplinas' => [
                [
                    'id' => $classeTurnoDisciplina->id,
                    'disciplina' => [
                        'id' => $classeTurnoDisciplina->disciplina?->id,
                        'nome' => $classeTurnoDisciplina->disciplina?->nome,
                    ],
                ],
            ],
        ]);
    }

    public function store(
        StoreProfessorRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        Gate::authorize('definirProfessor', new TurmaDisciplinaProfessor);

        // Valida que a turma pertence ao ano lectivo actual
        $anoLectivoActual = AnoLectivo::where('activo', 1)->first()?->id;

        abort_if($turma->ano_lectivo_id !== $anoLectivoActual, 403);

        if ($classeTurnoDisciplina->tem_professor && !$request->boolean('force')) {
            return back()->withErrors([
                'message' => 'Esta disciplina já tem um professor atribuído. Deseja substituí-lo?',
                'requires_confirmation' => true,
            ]);
        }

        DB::transaction(function () use ($request, $classeTurnoDisciplina, $turma) {
            TurmaDisciplinaProfessor::where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
                ->where('turma_id', $turma->id)
                ->delete();

            TurmaDisciplinaProfessor::create([
                'professor_id' => $request->professor_id,
                'turma_id' => $turma->id,
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
            ]);

            $classeTurnoDisciplina->update(['tem_professor' => true]);
        });

        return to_route('turmas.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
        ])->with('success', 'Professor associado com sucesso.');
    }
}
