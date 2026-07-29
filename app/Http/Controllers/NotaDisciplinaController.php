<?php

namespace App\Http\Controllers;

use App\Helpers\ArredondamentoHelper;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NotaDisciplinaController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {
    }

    /**
     * Lista as notas dos alunos de uma turma numa disciplina
     */
    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        // Gate::authorize('view', $tdp);

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        $sigla = $tdp->classeTurnoDisciplina->disciplina->sigla;

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/recurso/index', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'tdp' => $tdp->id,
            'disciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'sigla' => $sigla,
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
            ],
            'alunos' => $turmaAlunosEmRecurso->map(fn($ta) => [
                'turma_aluno_id' => $ta->id,
                'aluno_id' => $ta->aluno->id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'notas' => [
                    $sigla => [
                        'tdp_id' => $tdp->id,
                        'mf' => $ta->notas->firstWhere('periodo', 3)?->media_final,
                        'recurso' => $ta->notas->firstWhere('periodo', 4)?->media_final,
                    ],
                ],
            ])->values(),
        ]);
    }
    /**
     * Mostra o formulário de lançamento de notas dos alunos de uma turma numa disciplina
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->paginate(20, ['*'], 'page_alunos');

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/create', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
            'can' => [
                'create' => Auth::user()->can('create', [Nota::class, $tdp]),
            ],
            'data' => [
                'tdp_id' => $tdp->id,
                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                    'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                ],
                'alunos' => [
                    'data' => $turmaAlunos->getCollection()->map(fn($ta) => [
                        'turma_aluno_id' => $ta->id,
                        'aluno_id' => $ta->aluno->id,
                        'nome' => $ta->aluno->inscricao?->candidato?->nome,
                        'notas' => $ta->notas
                            ->map(fn($n) => $this->formatarNota($n))
                            ->keyBy('periodo'),
                    ]),
                    'current_page' => $turmaAlunos->currentPage(),
                    'last_page' => $turmaAlunos->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Salva as notas dos alunos de uma turma numa disciplina
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        string $classeTurnoDisciplinaId
    ) {
        $validated = $request->validate([
            'tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'periodo' => 'required|integer|in:1,2,3,4',
            'notas' => 'required|array',
            'notas.*.mac' => 'nullable|numeric|min:0|max:20',
            'notas.*.npp' => 'nullable|numeric|min:0|max:20',
            'notas.*.npt' => 'nullable|numeric|min:0|max:20',
            'notas.*.faltas' => 'nullable|integer|min:0',
            'notas.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        $tdp = TurmaDisciplinaProfessor::findOrFail($validated['tdp_id']);

        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);

        $this->notaService->lancarNotas(
            $validated['notas'],
            $validated['tdp_id'],
            (int) $validated['periodo'],
        );

        // Recalcular e persistir o resultado de cada aluno afectado
        $turmaAlunoIds = array_keys($validated['notas']);

        TurmaAluno::with([
            'aluno',
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->whereIn('id', $turmaAlunoIds)
            ->each(function (TurmaAluno $ta): void {
                $this->pautaService->actualizarResultadoAluno($ta);
            });

        return back();
    }

    /**
     * Função para formatar as notas de uma aluno de uma disciplina
     */
    private function formatarNota(Nota $n): array
    {
        return [
            'id' => $n->id,
            'periodo' => $n->periodo,
            'mac' => $n->mac,
            'nota_prova_professor' => $n->nota_prova_professor,
            'nota_prova_trimestral' => $n->nota_prova_trimestral,
            'media_trimestral' => ArredondamentoHelper::roundToHalf($n->media_trimestral),
            'media_final' => ArredondamentoHelper::roundToHalf($n->media_final),
            'faltas' => $n->faltas,
            'situacao_trimestral' => $n->situacao_trimestral,
            'situacao_anual' => $n->situacao_anual,
        ];
    }
}
