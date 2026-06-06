<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoTuteladoRequest;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceIndex;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CursoTuteladoController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:cursos.index', only: ['index']),
            new Middleware('permission:cursos.show', only: ['show']),
            new Middleware('permission:cursos.create', only: ['store']),
            new Middleware('permission:cursos.edit', only: ['update']),
            new Middleware('permission:cursos.delete', only: ['destroy']),
        ];
    }*/

    public function index(Instituicao $instituicao)
    {
        $cursosTutelados = CursoTutelado::query()
            ->whereHas('instituicaoCurso', fn ($q) => $q->where('instituicao_id', $instituicao->id))
            ->with([
                'instituicaoCurso.curso:id,nome',
                'instituicaoTutora:id,nome',
            ])
            ->get();

        return CursoTuteladoResourceIndex::collection($cursosTutelados);
    }

    public function create(Instituicao $instituicao)
    {
        $classes = Classe::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $cursos = Curso::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return Inertia::render('cursos-tutelados/create', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'classes' => $classes,
            'cursos' => $cursos,
        ]);
    }

    public function store(StoreCursoTuteladoRequest $request, Instituicao $instituicao)
    {
        $validated = $request->validated();

        // Obter ou criar o curso
        if ($validated['curso_id'] ?? null) {
            $curso = Curso::findOrFail($validated['curso_id']);
        } else {
            $curso = Curso::firstOrCreate(
                ['nome' => $validated['nome']],
                ['duracao_anos' => $validated['duracao_anos']]
            );
        }

        // Verificar se já existe essa associação
        $instituicaoCursoExistente = InstituicaoCurso::where('instituicao_id', $instituicao->id)
            ->where('curso_id', $curso->id)
            ->exists();

        if ($instituicaoCursoExistente) {
            abort(422, 'Esta instituição já tem este curso associado.');
        }

        // Usar transação para garantir integridade
        try {
            $cursoTutelado = null;

            DB::transaction(function () use ($instituicao, $curso, $validated, &$cursoTutelado) {
                // Criar InstituicaoCurso
                $instituicaoCurso = InstituicaoCurso::create([
                    'curso_id' => $curso->id,
                    'instituicao_id' => $instituicao->id,
                    'duracao_anos' => $validated['duracao_anos'] ?? 4,
                ]);

                // Criar CursoTutelado
                $cursoTutelado = $instituicaoCurso->cursoTutelado()->create([
                    'instituicao_tutora_id' => $instituicao->id,
                ]);

                // Associar classes
                $cursoTutelado->classes()->sync($validated['classes']);
            });
        } catch (\Exception $e) {
            abort(500, 'Erro ao criar curso tutelado: '.$e->getMessage());
        }

        return to_route('instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome,descricao',
            'instituicaoCurso.instituicao:id,nome',
            'instituicaoTutora:id,nome',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
            'cursoClasses.turnos.turmas.cursoClasseTurno.turno:id,nome',
            'cursoClasses.turnos.turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasses.turnos.classeTurnoDisciplinas.professores',  // ✅ para contar professores
            'cursoClasses.turnos.classeTurnoDisciplinas',              // ✅ para contar disciplinas
        ]);

        $resource = (new CursoTuteladoResourceShow($cursoTutelado))->resolve();

        return Inertia::render('cursos-tutelados/show', [
            'cursoTutelado' => $resource,
        ]);
    }

    public function edit(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome',
            'instituicaoCurso',
            'instituicaoTutora:id,nome',
            'classes:id',
        ]);

        $classes = Classe::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $instituicoes = Instituicao::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return Inertia::render('cursos-tutelados/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceEdit($cursoTutelado))->resolve(),
            'classes' => $classes,
            'instituicoes' => $instituicoes,
        ]);
    }

    public function update(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $validated = request()->validate([
            'instituicao_tutora_id' => ['required', 'string', 'exists:instituicoes,id'],
            'duracao_anos' => ['required', 'integer', 'min:1', 'max:10'],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['string', 'exists:classes,id'],
        ]);

        DB::transaction(function () use ($validated, $cursoTutelado) {
            $cursoTutelado->update([
                'instituicao_tutora_id' => $validated['instituicao_tutora_id'],
            ]);

            $cursoTutelado->instituicaoCurso->update([
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->classes()->sync($validated['classes']);
        });

        return to_route('instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso tutelado atualizado com sucesso!',
        ]);
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado): Response
    {
        $temTurmas = $cursoTutelado->cursoClasses
            ->flatMap(fn ($cc) => $cc->turnos)
            ->isNotEmpty();

        if ($temTurmas) {
            abort(422, 'Não é possível remover um curso que tem turmas associadas.');
        }

        $cursoTutelado->delete();

        return response()->noContent();
    }

    public function colegios(Instituicao $instituicao)
    {
        $colegios = Instituicao::whereHas(
            'instituicaoCursos.cursoTutelado',
            fn ($q) => $q->where('instituicao_tutora_id', $instituicao->id)
        )
            ->where('tipo', 'colegio') // ✅ apenas colégios
            ->with([
                'instituicaoCursos' => fn ($q) => $q->whereHas(
                    'cursoTutelado',
                    fn ($q) => $q->where('instituicao_tutora_id', $instituicao->id)
                )->with('curso:id,nome', 'cursoTutelado'),
            ])->get();

        return response()->json([
            'data' => $colegios->map(fn ($colegio) => [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'tipo' => $colegio->tipo,
                'cursos' => $colegio->instituicaoCursos->map(fn ($ic) => [
                    'id' => $ic->cursoTutelado->id,
                    'nome' => $ic->curso->nome,
                    'curso_tutelado_id' => $ic->cursoTutelado->id,
                ]),
            ]),
        ]);
    }

    public function alunos(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        abort_if($cursoTutelado->instituicao_tutora_id !== $instituicao->id, 403);

        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome',
            'instituicaoCurso.instituicao:id,nome,tipo',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
            'cursoClasses.turnos.turmas' => function ($q) {
                $q->with([
                    'alunosActivos.inscricao.candidato:id,nome',
                    'alunosActivos.user:id,email',
                    'gruposPap.professor.user:id,nome',
                    'gruposPap.elementos.aluno.inscricao.candidato:id,nome',
                    'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
                    'turmaDisciplinaProfessor.professor.user:id,nome',
                ]);
            },
        ]);

        return response()->json([
            'curso' => $cursoTutelado->instituicaoCurso->curso->nome,
            'colegio' => [
                'id' => $cursoTutelado->instituicaoCurso->instituicao->id,
                'nome' => $cursoTutelado->instituicaoCurso->instituicao->nome,
            ],
            'classes' => $cursoTutelado->cursoClasses->map(fn ($cc) => [
                'id' => $cc->id,
                'nome' => $cc->classe->nome,
                'turnos' => $cc->turnos->map(fn ($cct) => [
                    'id' => $cct->id,
                    'nome' => $cct->turno->nome,
                    'turmas' => $cct->turmas->map(fn ($turma) => [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                        'disciplinas' => $turma->turmaDisciplinaProfessor
                            ->groupBy('classe_turno_disciplina_id')
                            ->map(fn ($tdps) => [
                                'id' => $tdps->first()->classeTurnoDisciplina->disciplina->id,
                                'nome' => $tdps->first()->classeTurnoDisciplina->disciplina->nome,
                                'professor' => $tdps->first()->professor->user->nome,
                            ])->values(),
                        'grupos_pap' => $turma->gruposPap->map(fn ($grupo) => [
                            'id' => $grupo->id,
                            'nome_grupo' => $grupo->nome_grupo,
                            'tema_grupo' => $grupo->tema_grupo,
                            'status' => $grupo->status,
                            'nota_final' => $grupo->nota_final,
                            'data_defesa' => $grupo->data_defesa,
                            'professor' => $grupo->professor?->user?->nome,
                            'elementos' => $grupo->elementos->map(fn ($el) => [
                                'id' => $el->aluno_id,
                                'nome' => $el->aluno?->inscricao?->candidato?->nome,
                            ]),
                        ]),
                        'alunos' => $turma->alunosActivos->map(fn ($aluno) => [
                            'id' => $aluno->id,
                            'nome' => $aluno->inscricao?->candidato?->nome,
                            'matricula' => $aluno->matricula,
                            'email' => $aluno->user?->email,
                        ]),
                    ]),
                ]),
            ]),
        ]);
    }
}
