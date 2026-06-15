<?php

namespace App\Http\Controllers;

use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Disciplina;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turma;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClasseTurnoDisciplinaController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:disciplinas.index', only: ['index']),
            new Middleware('permission:disciplinas.create', only: ['store']),
            new Middleware('permission:disciplinas.edit', only: ['update']),
            new Middleware('permission:disciplinas.delete', only: ['destroy']),
        ];
    }*/

    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $disciplinas = $cursoClasseTurno->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla,componente',
                'turmaDisciplinaProfessores.professor.user:id,nome',
            ])
            ->paginate(5);

        return response()->json(
            $disciplinas->through(fn ($ctd) => [
                'id' => $ctd->id,
                'disciplina' => [
                    'id' => $ctd->disciplina->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'componente' => $ctd->disciplina->componente,
                ],
                'carga_horaria' => $ctd->carga_horaria,
                'tem_professor' => $ctd->tem_professor,
                'professor' => $ctd->turmaDisciplinaProfessores->first()?->professor ? [
                    'id' => $ctd->turmaDisciplinaProfessores->first()->professor->id,
                    'nome' => $ctd->turmaDisciplinaProfessores->first()->professor->user->nome,
                ] : null,
            ])
        );
    }

    public function create(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        return Inertia::render('cursos-tutelados/classes/turnos/disciplinas/create', [
            'disciplinas' => Disciplina::select('id', 'nome')->orderBy('nome')->get(),
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'classeId' => $cursoClasse->id,
            'turnoId' => $cursoClasseTurno->id,
        ]);
    }

    public function indexByTurma(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        // Obter disciplinas que têm professores associados a esta turma
        $disciplinas = ClasseTurnoDisciplina::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->with([
                'disciplina:id,nome,sigla,componente',
                'turmaDisciplinaProfessores' => function ($query) use ($turma) {
                    $query->where('turma_id', $turma->id)->with('professor.user:id,nome');
                },
            ])
            ->get();

        return response()->json(
            $disciplinas->map(fn ($ctd) => [
                'id' => $ctd->id,
                'disciplina' => [
                    'id' => $ctd->disciplina->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'componente' => $ctd->disciplina->componente,
                ],
                'carga_horaria' => $ctd->carga_horaria,
                'tem_professor' => $ctd->tem_professor,
                'professor' => $ctd->turmaDisciplinaProfessores->first()?->professor ? [
                    'id' => $ctd->turmaDisciplinaProfessores->first()->professor->id,
                    'nome' => $ctd->turmaDisciplinaProfessores->first()->professor->user->nome,
                ] : null,
            ])
        );
    }

    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno)
    {
        $request->validate([
            'disciplina_ids' => 'required|array|min:1',
            'disciplina_ids.*' => 'exists:disciplinas,id',
            'carga_horaria' => 'nullable|string|max:255',
            'tem_professor' => 'nullable|boolean',
        ]);

        // Buscar as que já existem para ignorar duplicadas
        $jaExistentes = ClasseTurnoDisciplina::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->whereIn('disciplina_id', $request->disciplina_ids)
            ->pluck('disciplina_id')
            ->toArray();

        $novas = array_diff($request->disciplina_ids, $jaExistentes);

        if (empty($novas)) {
            return back()->withErrors([
                'disciplina_ids' => 'Todas as disciplinas já estão associadas.',
            ]);
        }

        $disciplinasAdicionadas = [];
        foreach (array_values($novas) as $disciplinaId) {
            $ctd = ClasseTurnoDisciplina::create([
                'curso_classe_turno_id' => $cursoClasseTurno->id,
                'disciplina_id' => $disciplinaId,
                'carga_horaria' => $request->carga_horaria,
                'tem_professor' => $request->tem_professor ?? false,
            ]);
            $disciplinasAdicionadas[] = $ctd;
        }

        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
        ]);
    }

    public function update(
        Request $request,
        Instituicao $instituicao,
        InstituicaoCurso $instituicaoCurso,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        DB::transaction(function () use ($request, $classeTurnoDisciplina) {

            $classeTurnoDisciplina->update([
                'carga_horaria' => $request->carga_horaria,
                'tem_professor' => $request->filled('professor_id'),
            ]);

            // Actualizar professor se veio no request
            if ($request->filled('professor_id')) {
                TurmaDisciplinaProfessor::updateOrCreate(
                    ['classe_turno_disciplina_id' => $classeTurnoDisciplina->id],
                    ['professor_id' => $request->professor_id]
                );
            }
        });

        return response()->json(['message' => 'Actualizado com sucesso.'], 200);
    }

    public function destroy(CursoClasseTurno $cursoClasseTurno, ClasseTurnoDisciplina $classeTurnoDisciplina)
    {
        abort_if($classeTurnoDisciplina->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        // Verificar se tem professores associados
        $temProfessores = $classeTurnoDisciplina->turmaDisciplinaProfessores()->exists();

        if ($temProfessores) {
            return response()->json([
                'message' => 'Não é possível remover uma disciplina que tem professores associados.',
            ], 422);
        }

        $classeTurnoDisciplina->delete();

        return response()->json(['message' => 'Disciplina removida com sucesso.'], 204);
    }
}
