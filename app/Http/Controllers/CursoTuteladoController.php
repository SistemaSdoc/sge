<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoTuteladoRequest;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceIndex;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceShow;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceUpdate;

use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turma;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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

    public function index()
    {
        $query = CursoTutelado::query()
            ->with([
                'instituicaoCurso.curso:id,nome',
                'instituicaoTutora:id,nome',
            ]);

        $instituicaoId = Auth::user()->instituicao_id;

        if ($instituicaoId) {
            $query->whereHas('instituicaoCurso', fn($q) => $q->where('instituicao_id', $instituicaoId));
        }

        return;
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
            abort(500, 'Erro ao criar curso tutelado: ' . $e->getMessage());
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
            'cursoClasses.turnos.classeTurnoDisciplinas.professores',  // para contar professores
            'cursoClasses.turnos.classeTurnoDisciplinas',              // para contar disciplinas
            'professores.user:id,nome',                                // para listar professores
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

        // Só faz sentido para colégios — institutos não passam tutela
        $instituicoes = collect();

        if ($instituicao->tipo === 'colegio') {
            $cursoId = $cursoTutelado->instituicaoCurso->curso_id;

            $instituicoes = Instituicao::select('id', 'nome')
                ->where(function ($q) use ($cursoId, $cursoTutelado) {
                    // Institutos que têm o curso
                    $q->where('tipo', 'instituto')
                        ->whereHas('instituicaoCursos', fn($q) => $q->where('curso_id', $cursoId));
                })
                ->orWhere('id', $cursoTutelado->instituicao_tutora_id) // Garante que a tutora actual aparece sempre
                ->orderBy('nome')
                ->get();
        } else {
            $instituicoes = Instituicao::select('id', 'nome')
                ->where('id', $cursoTutelado->instituicao_tutora_id)
                ->get();
        }

        return Inertia::render('cursos-tutelados/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'tipo' => $instituicao->tipo,
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

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $temTurmas = $cursoTutelado->cursoClasses
            ->flatMap(fn($cc) => $cc->turnos)
            ->isNotEmpty();

        if ($temTurmas) {
            abort(422, 'Não é possível remover um curso que tem turmas associadas.');
        }

        $cursoTutelado->delete();

        return response()->noContent();
    }

    public function colegios(Instituicao $instituicao)
    {
        // 1. Buscar IDs dos cursos tutelados desta instituição tutora
        $cursoTuteladoIds = CursoTutelado::where('instituicao_tutora_id', $instituicao->id)
            ->pluck('instituicao_curso_id');

        // 2. Buscar IDs das instituições (colégios) que têm esses cursos
        $instituicaoIds = InstituicaoCurso::whereIn('id', $cursoTuteladoIds)
            ->distinct()
            ->pluck('instituicao_id');

        // 3. Paginar os colégios diretamente
        $colegios = Instituicao::whereIn('id', $instituicaoIds)
            ->where('tipo', 'colegio')
            ->select('id', 'nome', 'tipo')
            ->orderBy('nome')
            ->paginate(5);

        // 4. Carregar os cursos de cada colégio (já filtrados) em query separada
        $colegiosComCursos = $colegios->getCollection()->map(function ($colegio) use ($instituicao) {
            $cursos = InstituicaoCurso::where('instituicao_id', $colegio->id)
                ->whereHas('cursoTutelado', fn($q) => $q->where('instituicao_tutora_id', $instituicao->id))
                ->with(['curso:id,nome', 'cursoTutelado:id,instituicao_curso_id'])
                ->get();

            return [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'tipo' => $colegio->tipo,
                'cursos' => $cursos->map(fn($ic) => [
                    'id' => $ic->cursoTutelado->id,
                    'nome' => $ic->curso->nome,
                    'curso_tutelado_id' => $ic->cursoTutelado->id,
                ]),
            ];
        });

        // 5. Substituir a collection do paginator pelos dados transformados
        $colegios->setCollection($colegiosComCursos);

        return Inertia::render('colegios/index', [
            'instituicao' => ['id' => $instituicao->id, 'nome' => $instituicao->nome],
            'colegios' => $colegios,
        ]);
    }

    public function alunos(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        abort_if($cursoTutelado->instituicao_tutora_id !== $instituicao->id, 403);

        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome',
            'instituicaoCurso.instituicao:id,nome,tipo',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
        ]);

        // Paginar turmas em vez de carregar todas
        $turmasPaginadas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn($q) =>
            $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
            ->with([
                'cursoClasseTurno.cursoClasse.classe:id,nome',
                'cursoClasseTurno.turno:id,nome',
                'alunosActivos' => fn($q) => $q->wherePivot('activo', true)
                    ->with(['inscricao.candidato:id,nome', 'user:id,email'])
                    ->take(50),
                'gruposPap.professor.user:id,nome',
                'gruposPap.elementos.aluno.inscricao.candidato:id,nome',
                'turmaDisciplinaProfessor.professor.user:id,nome',
                'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            ])
            ->orderBy('nome')
            ->paginate(5);

        // Mapear turmas paginadas
        $turmasMapeadas = $turmasPaginadas->getCollection()->map(fn($turma) => [
            'id' => $turma->id,
            'nome' => $turma->nome,
            'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'turno' => $turma->cursoClasseTurno?->turno?->nome,
            'cursoClasse' => ['id' => $turma->cursoClasseTurno?->cursoClasse?->id],
            'cursoClasseTurno' => ['id' => $turma->cursoClasseTurno?->id],
            'disciplinas' => $turma->turmaDisciplinaProfessor
                ->groupBy('classe_turno_disciplina_id')
                ->map(fn($tdps) => [
                    'id' => $tdps->first()->classeTurnoDisciplina->disciplina->id,
                    'nome' => $tdps->first()->classeTurnoDisciplina->disciplina->nome,
                    'professor' => $tdps->first()->professor->user->nome,
                ])->values(),
            'grupos_pap' => $turma->gruposPap->map(fn($grupo) => [
                'id' => $grupo->id,
                'nome_grupo' => $grupo->nome_grupo,
                'tema_grupo' => $grupo->tema_grupo,
                'status' => $grupo->status,
                'nota_final' => $grupo->nota_final,
                'data_defesa' => $grupo->data_defesa,
                'professor' => $grupo->professor?->user?->nome,
                'elementos' => $grupo->elementos->map(fn($el) => [
                    'id' => $el->aluno_id,
                    'nome' => $el->aluno?->inscricao?->candidato?->nome,
                ]),
            ]),
            'alunos' => $turma->alunosActivos->map(fn($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'matricula' => $aluno->matricula,
                'email' => $aluno->user?->email,
            ]),
        ]);

        $turmasPaginadas->setCollection($turmasMapeadas);

        // Agrupar turmas por classe/turno para manter estrutura aninhada no frontend
        $classesAgrupadas = $cursoTutelado->cursoClasses->map(fn($cc) => [
            'id' => $cc->id,
            'nome' => $cc->classe?->nome,
            'turnos' => $cc->turnos->map(fn($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno?->nome,
                'turmas' => $turmasPaginadas->getCollection()
                    ->where('cursoClasseTurno.id', $cct->id)
                    ->values(),
            ])->filter(fn($turno) => $turno['turmas']->isNotEmpty())->values(),
        ])->filter(fn($classe) => $classe['turnos']->isNotEmpty())->values();

        return Inertia::render('colegios/curso-show', [
            'instituicao' => ['id' => $instituicao->id, 'nome' => $instituicao->nome],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => $cursoTutelado->instituicaoCurso?->curso?->nome,
                'colegio' => [
                    'id' => $cursoTutelado->instituicaoCurso?->instituicao?->id,
                    'nome' => $cursoTutelado->instituicaoCurso?->instituicao?->nome,
                ],
                'classes' => $classesAgrupadas,
            ],
            'turmasPagination' => [
                'data' => $turmasPaginadas->items(),
                'current_page' => $turmasPaginadas->currentPage(),
                'last_page' => $turmasPaginadas->lastPage(),
                'per_page' => $turmasPaginadas->perPage(),
                'total' => $turmasPaginadas->total(),
                'links' => $turmasPaginadas->linkCollection(),
            ],
        ]);
    }

    public function showColegio(Instituicao $instituicao, Instituicao $colegio)
    {
        $colegio->load([
            'instituicaoCursos' => fn($q) => $q->whereHas(
                'cursoTutelado',
                fn($q) => $q->where('instituicao_tutora_id', $instituicao->id)
            )->with('curso:id,nome', 'cursoTutelado:id,instituicao_curso_id'),
        ]);

        return Inertia::render('colegios/show', [
            'colegio' => [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'cursos' => $colegio->instituicaoCursos
                    ->filter(fn($ic) => $ic->cursoTutelado !== null)
                    ->map(fn($ic) => [
                        'id' => $ic->curso->id,
                        'nome' => $ic->curso->nome,
                        'curso_tutelado_id' => $ic->cursoTutelado->id,
                    ])->values(),
            ],
            'instituicao' => ['id' => $instituicao->id],
        ]);
    }
}