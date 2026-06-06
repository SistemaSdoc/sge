<?php

namespace App\Http\Controllers\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicaoCurso\StoreProfessorRequest;
use App\Http\Requests\InstituicaoCurso\UpdateProfessorTurnosRequest;
use App\Http\Resources\ProfessorResource;
use App\Models\InstituicaoCurso;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\TurnoDisciplinaProfessor;
use App\Models\CursoTutelado;
use App\Models\CursoClasseTurno;
use App\Models\ClasseTurnoDisciplina;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\Turma;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class TurmaDisciplinaProfessorController extends Controller //implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:professores.index', only: ['index']),
            new Middleware('permission:professores.show', only: ['show']),
            new Middleware('permission:professores.create', only: ['store']),
            new Middleware('permission:professores.edit', only: ['update']),
            new Middleware('permission:professores.delete', only: ['destroy']),
        ];
    }*/

    /**
     * Lista professores associados a um curso específico na instituição
     */

    /* public function index(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        // CORRIGIDO: Buscar via curso_tutelado -> curso_classe -> curso_classe_turno -> ...
        $professores = Professor::whereHas(
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.cursoClasse.cursoTutelado',
            fn($q) => $q->where('instituicao_curso_id', $instituicaoCurso->id)
        )->with([
            'user:id,nome,email',
            'turmaDisciplinaProfessor' => fn($q) => $q->whereHas(
                'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado',
                fn($q) => $q->where('instituicao_curso_id', $instituicaoCurso->id)
            )->with([
                'classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
                'classeTurnoDisciplina.disciplina:id,nome',
            ]),
        ])->get();

        return ProfessorResource::collection($professores);
    } */
    public function index(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::when(
            $instituicaoId,
            fn($q) => $q->whereHas(
                'user',
                fn($q) => $q->where('instituicao_id', $instituicaoId)
            )
        )->with(['user:id,nome,telefone'])
            ->get();

        // Usa o Resource para consistência
        return ProfessorResource::collection($professores);
    }

    /**
     * Associa professor a turnos/disciplinas do curso
     */
    public function store(
        StoreProfessorRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        if ($classeTurnoDisciplina->tem_professor) {
            return response()->json(['message' => 'Esta disciplina já tem professor.'], 422);
        }

        DB::transaction(function () use ($request, $classeTurnoDisciplina, $turma) {
            TurmaDisciplinaProfessor::create([
                'professor_id' => $request->professor_id,
                'turma_id' => $turma->id,
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id, // ← modelo injetado
            ]);

            $classeTurnoDisciplina->update(['tem_professor' => true]);
        });

        return response()->json(['message' => 'Professor associado com sucesso.'], 201);
    }

    /**
     * Mostra detalhes do professor no curso
     */
    public function show(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Professor $professore)
    {
        $professore->load([
            'user:id,nome,email,bi,telefone',
            'turnoDisciplinaProfessor' => fn($q) => $q->whereHas(
                'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado',
                fn($q) => $q->where('curso_instituicao_id', $instituicaoCurso->id)
            )->with([
                        'classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
                        'classeTurnoDisciplina.disciplina:id,nome,sigla',
                    ]),
            // CORRIGIDO: Turmas via turma_professor
            'turmas' => fn($q) => $q->whereHas(
                'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado',
                fn($q) => $q->where('curso_instituicao_id', $instituicaoCurso->id)
            ),
        ]);

        return new ProfessorResource($professore);
    }

    /**
     * Atualiza turnos do professor no curso
     */
    public function update(UpdateProfessorTurnosRequest $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Professor $professore)
    {
        $cursoTutelado = CursoTutelado::where('curso_instituicao_id', $instituicaoCurso->id)->first();

        if (!$cursoTutelado) {
            return response()->json(['message' => 'Curso não encontrado nesta instituição.'], 404);
        }

        $turnosValidos = CursoClasseTurno::whereHas('cursoClasse', fn($q) => $q->where('curso_tutelado_id', $cursoTutelado->id))
            ->whereIn('turno_id', $request->turnos)
            ->pluck('turno_id');

        if ($turnosValidos->count() !== count($request->turnos)) {
            return response()->json(['message' => 'Um ou mais turnos são inválidos para este curso.'], 422);
        }

        $classeTurnoIdsDoCurso = ClasseTurnoDisciplina::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )->pluck('id');

        DB::transaction(function () use ($request, $professore, $cursoTutelado, $classeTurnoIdsDoCurso) {
            // Remover associações antigas
            TurnoDisciplinaProfessor::where('professor_id', $professore->id)
                ->whereIn('classe_turno_disciplina_id', $classeTurnoIdsDoCurso)
                ->delete();

            // Resetar flags das disciplinas afetadas ← corrigido
            ClasseTurnoDisciplina::whereIn('id', $classeTurnoIdsDoCurso)
                ->update(['tem_professor' => false]);

            // Criar novas associações
            foreach ($request->turnos as $turnoId) {
                $classeTurnoDisciplinas = ClasseTurnoDisciplina::whereHas(
                    'cursoClasseTurno',
                    fn($q) => $q->where('turno_id', $turnoId)
                        ->whereHas('cursoClasse', fn($q2) => $q2->where('curso_tutelado_id', $cursoTutelado->id))
                )->get();

                foreach ($classeTurnoDisciplinas as $ctd) {
                    TurnoDisciplinaProfessor::create([
                        'professor_id' => $professore->id,
                        'classe_turno_disciplina_id' => $ctd->id,
                    ]);

                    $ctd->update(['tem_professor' => true]); // ← corrigido
                }
            }
        });

        $professore->load([
            'user:id,nome,email',
            'turnoDisciplinaProfessor' => fn($q) => $q->whereHas(
                'classeTurnoDisciplina.cursoClasseTurno.cursoTutelado',
                fn($q) => $q->where('curso_instituicao_id', $instituicaoCurso->id)
            )->with('classeTurnoDisciplina.cursoClasseTurno.turno:id,nome'),
        ]);

        return new ProfessorResource($professore);
    }

    /**
     * Remove professor do curso
     */
    public function destroy(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso, Professor $professore)
    {
        $cursoTutelado = CursoTutelado::where('curso_instituicao_id', $instituicaoCurso->id)->first();

        if (!$cursoTutelado) {
            return response()->json(['message' => 'Curso não encontrado.'], 404);
        }

        $classeTurnoIdsDoCurso = ClasseTurnoDisciplina::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )->pluck('id');

        DB::transaction(function () use ($professore, $classeTurnoIdsDoCurso) {
            TurnoDisciplinaProfessor::where('professor_id', $professore->id)
                ->whereIn('classe_turno_disciplina_id', $classeTurnoIdsDoCurso)
                ->delete();

            // Resetar flags ← corrigido
            ClasseTurnoDisciplina::whereIn('id', $classeTurnoIdsDoCurso)
                ->where('tem_professor', true)
                ->update(['tem_professor' => false]);
        });

        return response()->json(['message' => 'Professor removido do curso com sucesso.']);
    }
}
