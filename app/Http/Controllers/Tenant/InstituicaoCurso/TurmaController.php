<?php

namespace App\Http\Controllers\Tenant\InstituicaoCurso;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicaoCurso\StoreTurmaRequest;
use App\Http\Requests\InstituicaoCurso\UpdateTurmaRequest;
use App\Http\Resources\TurmaResource;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class TurmaController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:turmas.index', only: ['index']),
            new Middleware('permission:turmas.show', only: ['show']),
            new Middleware('permission:turmas.create', only: ['store', 'adicionarProfessor']),
            new Middleware('permission:turmas.edit', only: ['update']),
            new Middleware('permission:turmas.delete', only: ['destroy', 'removerProfessor']),
        ];
    }*/

    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $user = auth()->user();
        $query = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        );

        if (! $user?->isSuperAdmin() && ! $user?->isDirector()) {
            $professor = $user?->professor;

            if (! $professor) {
                return TurmaResource::collection(collect());
            }

            $query->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id));
        }

        $turmas = $query->with([
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
        ])
            ->get();

        return TurmaResource::collection($turmas);
    }

    public function store(StoreTurmaRequest $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $validated = $request->validated();

        // verifica se o curso_classe_turno pertence ao curso tutelado
        $valido = CursoClasseTurno::where('id', $validated['curso_classe_turno_id'])
            ->whereHas(
                'cursoClasse',
                fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
            )
            ->exists();

        if (! $valido) {
            return response()->json(['message' => 'Classe/turno inválido para este curso.'], 422);
        }

        $turma = Turma::create($validated);

        return new TurmaResource($turma->load([
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
        ]));
    }

    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma)
    {
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

        return new TurmaResource($turma);
    }

    public function update(UpdateTurmaRequest $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma)
    {
        $turma->update($request->validated());

        return new TurmaResource($turma->load(
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome'
        ));
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma)
    {
        $turma->delete();

        return response()->json(['message' => 'Turma removida com sucesso.']);
    }

    /* public function adicionarDisciplinaProfessor(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma)
    {
        $request->validate([
            'professor_id'  => ['required', 'exists:professores,id'],
            'disciplina_id' => ['required', 'exists:disciplinas,id'],
            #'carga_horaria' => ['required'],
        ]);

        $cursoClasseTurno = $turma->cursoClasseTurno;

        if (!$cursoClasseTurno) {
            return response()->json(['message' => 'Turma sem turno associado.'], 422);
        }

        DB::transaction(function () use ($request, $cursoClasseTurno, $turma) {

            // Cria a ClasseTurnoDisciplina
            $ctd = ClasseTurnoDisciplina::create([
                'curso_classe_turno_id' => $cursoClasseTurno->id,
                'disciplina_id'         => $request->disciplina_id,
                #'carga_horaria'         => $request->carga_horaria,
                'tem_professor'         => true,
            ]);

            // Associa o professor
            if ($request->filled('professor_id')) {
                TurmaDisciplinaProfessor::create([
                    'professor_id'               => $request->professor_id,
                    'classe_turno_disciplina_id' => $ctd->id,
                    'turma_id'                   => $turma->id,
                ]);
            }
        });

        return response()->json(['message' => 'Professor associado com sucesso.'], 201);
    }

    public function removerProfessor(Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma, Professor $professor)
    {
        $turma->professores()->detach($professor->id);

        return response()->json(['message' => 'Professor removido da turma com sucesso.']);
    } */
}
