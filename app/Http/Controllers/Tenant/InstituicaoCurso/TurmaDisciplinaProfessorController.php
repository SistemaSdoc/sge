<?php

namespace App\Http\Controllers\Tenant\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicaoCurso\StoreProfessorRequest;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
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

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        $classeTurnoDisciplina->load('disciplina');

        $professores = $cursoTutelado->professores()
            ->with('user:id,nome')
            ->get()
            ->map(fn (Professor $professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ]);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/disciplinas/professores/create', [
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
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
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

        $request->validate([
            'ano_lectivo_id' => 'nullable|exists:ano_lectivos,id',
        ]);

        $jaExisteNaTurma = TurmaDisciplinaProfessor::where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->where('turma_id', $turma->id)
            ->exists();

        if ($jaExisteNaTurma && ! $request->boolean('force')) {
            return back()->withErrors([
                'message' => 'Já existe um professor atribuído a esta disciplina nesta turma. Deseja substituí-lo?',
                'requires_confirmation' => true,
            ]);
        }

        $anoLectivoId = $request->input('ano_lectivo_id')
            ?? request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        DB::transaction(function () use ($request, $classeTurnoDisciplina, $turma) {
            $turmaDisciplinaProfessor = TurmaDisciplinaProfessor::where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
                ->where('turma_id', $turma->id)
                ->first();

            if ($turmaDisciplinaProfessor) {
                $turmaDisciplinaProfessor->update([
                    'professor_id' => $request->professor_id,
                ]);
            } else {
                TurmaDisciplinaProfessor::create([
                    'professor_id' => $request->professor_id,
                    'turma_id' => $turma->id,
                    'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                ]);
            }

            $classeTurnoDisciplina->update(['tem_professor' => true]);
        });

        $anoLectivoParam = $anoLectivoId ? ['ano_lectivo_id' => $anoLectivoId] : [];

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
        ] + $anoLectivoParam)->with('success', 'Professor associado com sucesso.');
    }
}
