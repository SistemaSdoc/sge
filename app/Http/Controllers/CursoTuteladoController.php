<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoTuteladoRequest;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\NivelEnsino;
use App\Models\Turma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CursoTuteladoController extends Controller
{
    public function create(Instituicao $instituicao)
    {
        Gate::authorize('create', CursoTutelado::class);

        $classes = Classe::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $niveisEnsino = NivelEnsino::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $cursos = Curso::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return Inertia::render('cursos-tutelados/create', [
            'instituicao' => $instituicao->only('id'),
            'classes' => $classes,
            'cursos' => $cursos,
            'niveisEnsino' => $niveisEnsino,
        ]);
    }

    public function store(
        StoreCursoTuteladoRequest $request,
        Instituicao $instituicao
    ): RedirectResponse {
        Gate::authorize('create', CursoTutelado::class);

        $validated = $request->validated();

        // Obter ou criar curso
        $curso = isset($validated['curso_id'])
            ? Curso::findOrFail($validated['curso_id'])
            : Curso::firstOrCreate(
                ['nome' => $validated['nome']],
                ['duracao_anos' => $validated['duracao_anos']]
            );

        // Verificar duplicado antes de entrar na transação
        if (InstituicaoCurso::where('instituicao_id', $instituicao->id)
            ->where('curso_id', $curso->id)
            ->exists()) {
            return back()->withErrors([
                'curso_id' => 'Esta instituição já tem este curso associado.',
            ]);
        }

        DB::transaction(function () use ($instituicao, $curso, $validated) {
            $instituicaoCurso = InstituicaoCurso::create([
                'curso_id' => $curso->id,
                'instituicao_id' => $instituicao->id,
                'duracao_anos' => $validated['duracao_anos'] ?? $curso->duracao_anos,
            ]);

            $cursoTutelado = $instituicaoCurso->cursoTutelado()->create([
                'instituicao_tutora_id' => $instituicao->id,
            ]);

            // Insert bulk — uma única query independentemente do nº de classes
            $now = now();
            CursoClasse::insert(
                collect($validated['classe_ids'])->map(fn ($classeId) => [
                    'id' => (string) Str::uuid7(),
                    'curso_tutelado_id' => $cursoTutelado->id,
                    'classe_id' => $classeId,
                    'nivel_ensino_id' => $validated['nivel_ensino_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        return to_route('instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('view', $cursoTutelado);

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome,descricao',
            'instituicaoCurso.instituicao:id,nome',
            'instituicaoTutora:id,nome',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
            'cursoClasses.turnos' => function ($query) use ($anoLectivoId) {
                $query->with([
                    'turmas' => fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId),  // ← filtro aqui agora
                    'turmas.cursoClasseTurno.turno:id,nome',
                    'turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
                    'classeTurnoDisciplinas.professores',
                    'classeTurnoDisciplinas',
                ]);
            },
            'professores.user:id,nome',
        ]);

        $resource = (new CursoTuteladoResourceShow($cursoTutelado))->resolve();

        return Inertia::render('cursos-tutelados/show', [
            'cursoTutelado' => $resource,
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
        ]);
    }

    public function edit(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('update', $cursoTutelado);

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
                ->where(function ($q) use ($cursoId) {
                    // Institutos que têm o curso
                    $q->where('tipo', 'instituto')
                        ->whereHas('instituicaoCursos', fn ($q) => $q->where('curso_id', $cursoId));
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
        Gate::authorize('update', $cursoTutelado);

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
        Gate::authorize('update', $cursoTutelado);

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
                ->whereHas('cursoTutelado', fn ($q) => $q->where('instituicao_tutora_id', $instituicao->id))
                ->with(['curso:id,nome', 'cursoTutelado:id,instituicao_curso_id'])
                ->get();

            return [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'tipo' => $colegio->tipo,
                'cursos' => $cursos->map(fn ($ic) => [
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

        // ✅ Carrega tudo que precisa em UMA query
        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
            ->with([
                'cursoClasseTurno.cursoClasse.classe:id,nome',
                'cursoClasseTurno.turno:id,nome',
                'alunosActivos' => fn ($q) => $q->wherePivot('activo', true)
                    ->with(['inscricao.candidato:id,nome', 'user:id,email'])
                    ->take(50),
                'gruposPap.professor.user:id,nome',
                'gruposPap.elementos.aluno.inscricao.candidato:id,nome',
            ])
            ->orderBy('nome')
            ->get();

        // ✅ Mapeia as turmas

        // Mapear turmas paginadas
        $turmasMapeadas = $turmasPaginadas->getCollection()->map(fn ($turma) => [
            'id' => $turma->id,
            'nome' => $turma->nome,
            'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'turno' => $turma->cursoClasseTurno?->turno?->nome,
            'cursoClasse' => ['id' => $turma->cursoClasseTurno?->cursoClasse?->id],
            'cursoClasseTurno' => ['id' => $turma->cursoClasseTurno?->id],
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
            // ✅ PAUTA - Estrutura correcta
            'pauta' => [
                'disciplinas' => $turma->turmaDisciplinaProfessor
                    ->pluck('classeTurnoDisciplina.disciplina.nome')
                    ->unique()
                    ->values()
                    ->toArray(),
                'alunos' => $turma->turmaAlunos->map(fn($turmaAluno) => [
                    'aluno_id' => $turmaAluno->aluno->id,
                    'numero' => $turmaAluno->aluno->matricula ?? '—',
                    'nome' => $turmaAluno->aluno->inscricao?->candidato?->nome,
                    // ✅ CORRIGIDO: Estrutura correcta para o React
                    'notas' => $turma->turmaDisciplinaProfessor
                        ->mapWithKeys(fn($tdp) => [
                            $tdp->classeTurnoDisciplina->disciplina->nome => [
                                'media' => $turmaAluno->notas
                                    ->where('turma_disciplina_professor_id', $tdp->id)
                                    ->where('periodo', $periodo)  // ✅ Usa o período correcto
                                    ->first()?->media_trimestral,  // ← Pega media_trimestral
                                'mf' => $turmaAluno->notas
                                    ->where('turma_disciplina_professor_id', $tdp->id)
                                    ->where('periodo', $periodo)
                                    ->first()?->media_final,  // ← Pega media_final
                            ]
                        ])
                        ->toArray(),
                    'total_faltas' => $turmaAluno->notas->sum('faltas'),
                    'resultado' => 'Aprovado'
                ])->toArray(),
            ],
        ]);

        $turmasPaginadas->setCollection($turmasMapeadas);

        // Agrupar turmas por classe/turno para manter estrutura aninhada no frontend
        $classesAgrupadas = $cursoTutelado->cursoClasses->map(fn ($cc) => [
            'id' => $cc->id,
            'nome' => $cc->classe?->nome,
            'turnos' => $cc->turnos->map(fn ($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno?->nome,
                'turmas' => $turmasMapeadas
                    ->where('cursoClasseTurno.id', $cct->id)
                    ->values(),
            ])->filter(fn ($turno) => $turno['turmas']->isNotEmpty())->values(),
        ])->filter(fn ($classe) => $classe['turnos']->isNotEmpty())->values();

        return Inertia::render('colegios/curso-show', [
            'instituicao' => ['id' => $instituicao->id, 'nome' => $instituicao->nome],
            'instituicaoId' => $instituicao->id,
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => $cursoTutelado->instituicaoCurso?->curso?->nome,
                'colegio' => [
                    'id' => $cursoTutelado->instituicaoCurso?->instituicao?->id,
                    'nome' => $cursoTutelado->instituicaoCurso?->instituicao?->nome,
                ],
                'classes' => $classesAgrupadas,
            ],
        ]);
    }

    public function pauta(Instituicao $instituicao, CursoTutelado $cursoTutelado, Turma $turma)
    {
        abort_if($cursoTutelado->instituicao_tutora_id !== $instituicao->id, 403);

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
        ]);

        $alunosActivos = $turma->alunosActivos()
            ->with([
                'inscricao.candidato:id,nome',
                'user:id,email',
                'notas' => fn($q) => $q->select(
                    'id',
                    'turma_aluno_id',
                    'turmaDisciplinaProfessor_id',
                    'periodo',
                    'mac',
                    'nota_prova_professor',
                    'nota_prova_trimestral',
                    'media_trimestral',
                    'media_final',
                    'faltas',
                    'situacao_trimestral',
                    'situacao_anual'
                )
            ])
            ->get();

        $disciplinas = $turma->turmaDisciplinaProfessor()
            ->with([
                'classeTurnoDisciplina.disciplina:id,nome',
                'professor.user:id,nome',
            ])
            ->get();

        $periodos = [1, 2, 3, 4];

        return Inertia::render('colegios/pauta-show', [
            'instituicao' => ['id' => $instituicao->id],
            'cursoTutelado' => ['id' => $cursoTutelado->id],
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'pauta' => [
                'periodos' => $periodos,
                'disciplinas' => $disciplinas->map(fn($tdp) => [
                    'id' => $tdp->id,
                    'nome' => $tdp->classeTurnoDisciplina?->disciplina?->nome,
                    'professor' => $tdp->professor?->user?->nome,
                ])->values(),
                'alunos' => $alunosActivos->map(fn($aluno) => [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome,
                    'matricula' => $aluno->matricula,
                    'notas_por_disciplina' => $disciplinas->map(fn($tdp) => [
                        'disciplina_id' => $tdp->id,
                        'notas_por_periodo' => collect($periodos)->map(fn($periodo) => [
                            'periodo' => $periodo,
                            'nota' => $aluno->notas
                                ->where('turmaDisciplinaProfessor_id', $tdp->id)
                                ->where('periodo', $periodo)
                                ->first()?->only([
                                        'mac',
                                        'nota_prova_professor',
                                        'nota_prova_trimestral',
                                        'media_trimestral',
                                        'media_final',
                                        'faltas',
                                        'situacao_trimestral',
                                        'situacao_anual',
                                    ]),
                        ])->toArray(),
                    ])->toArray(),
                ])->toArray(),
            ],
        ]);
    }

    public function showColegio(Instituicao $instituicao, Instituicao $colegio)
    {
        $colegio->load([
            'instituicaoCursos' => fn ($q) => $q->whereHas(
                'cursoTutelado',
                fn ($q) => $q->where('instituicao_tutora_id', $instituicao->id)
            )->with('curso:id,nome', 'cursoTutelado:id,instituicao_curso_id'),
        ]);

        return Inertia::render('colegios/show', [
            'colegio' => [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'cursos' => $colegio->instituicaoCursos
                    ->filter(fn ($ic) => $ic->cursoTutelado !== null)
                    ->map(fn ($ic) => [
                        'id' => $ic->curso->id,
                        'nome' => $ic->curso->nome,
                        'curso_tutelado_id' => $ic->cursoTutelado->id,
                    ])->values(),
            ],
            'instituicao' => ['id' => $instituicao->id],
        ]);
    }
}
