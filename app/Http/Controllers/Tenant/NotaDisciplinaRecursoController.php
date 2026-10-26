<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\User;
use App\Services\Tenant\NotaService;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NotaDisciplinaRecursoController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {}

    /**
     * Lista as notas das provas do recurso dos alunos de uma turma ...
     */
    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina,
        Request $request
    ) {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        Gate::authorize('view', $tdp);
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id)
                ->whereIn('periodo', [3, 4]),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->where(function ($query) use ($tdp): void {
                $query
                    ->whereHas('notas', fn ($q) => $q
                        ->where('turma_disciplina_professor_id', $tdp->id)
                        ->where('periodo', 3)
                        ->whereNotNull('media_final')
                        ->where('media_final', '>=', 7)
                        ->where('media_final', '<', 10))
                    ->orWhereHas('notas', fn ($q) => $q
                        ->where('turma_disciplina_professor_id', $tdp->id)
                        ->where('periodo', 4));
            })
            ->orderBy('id')
            ->paginate(20, ['*'], 'page_alunos');

        $alunos = $turmaAlunos->getCollection()->map(fn ($ta) => [
            'turma_aluno_id' => $ta->id,
            'aluno_id' => $ta->aluno->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'tdp_id' => $tdp->id,
            'media_final_p3' => $ta->notas->firstWhere('periodo', 3)?->media_final,
            'nota_recurso' => $ta->notas->firstWhere('periodo', 4)?->media_trimestral,
        ])->all();

        // ✅ Renderizar a página correcta do recurso
        return Inertia::render(
            'tenant/cursos-tutelados/classes/turnos/turmas/disciplinas/notas-recurso/create',
            [
                'instituicao' => [
                    'id' => $instituicao->id,
                ],
                'cursoTutelado' => [
                    'id' => $cursoTutelado->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                ],
                'cursoClasse' => [
                    'id' => $cursoClasse->id,
                    'nome' => $cursoClasse->classe->nome,
                ],
                'cursoClasseTurno' => [
                    'id' => $cursoClasseTurno->id,
                    'nome' => $cursoClasseTurno->turno->nome,
                ],
                'turma' => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                ],
                'can' => [
                    'curso' => [
                        'view' => $user->can('view', $cursoTutelado),
                    ],
                    'classe' => [
                        'view' => $user->can('view', $cursoClasse),
                    ],
                    'turno' => [
                        'view' => $user->can('view', $cursoClasseTurno),
                    ],
                    'turma' => [
                        'view' => $user->can('view', $turma),
                    ],
                ],
                'classeTurnoDisciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                ],
                'alunos' => [
                    'data' => $alunos,
                    'current_page' => $turmaAlunos->currentPage(),
                    'last_page' => $turmaAlunos->lastPage(),
                ],
                'pode_lancar_recurso' => true, // lógica de permissão aqui se necessário
                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                ],
            ]
        );
    }

    /**
     * Mostra o formulário de lançamento das notas das provas do recurso dos alunos de uma turma ...
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
    ) {
        $pauta = $this->pautaService->gerarPauta($turma, 4, 5);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/notas/recurso/create', [
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'turmaId' => $turma->id,
            'turma' => $pauta['turma'],
            'disciplinas' => $pauta['disciplinas'],
            'resumo' => $pauta['resumo'],
            'alunos' => $pauta['alunos'],
        ]);
    }

    /**
     * Salva as notas das provas do recurso dos alunos de uma turma ...
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $tdp = TurmaDisciplinaProfessor::query()
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        Gate::authorize('view', $tdp);
        Gate::authorize('create', [Nota::class, $tdp]);

        $validated = $request->validate([
            'lancamentos' => 'required|array|min:1',
            'lancamentos.*.turma_aluno_id' => [
                'required',
                Rule::exists('turma_aluno', 'id')->where('turma_id', $turma->id),
            ],
            'lancamentos.*.tdp_id' => ['required', Rule::in([$tdp->id])],
            'lancamentos.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        if (! $this->notaService->periodoPodeSerLancado($tdp->id, 4)) {
            throw ValidationException::withMessages([
                'lancamentos' => 'Primeiro lança os três trimestres anteriores para continuar.',
            ]);
        }

        $notasPorAluno = collect($validated['lancamentos'])
            ->keyBy('turma_aluno_id')
            ->map(fn ($lancamento) => [
                'nota_recurso' => $lancamento['nota_recurso'],
            ])
            ->all();

        $this->notaService->lancarNotas($notasPorAluno, $tdp->id, 4);

        return back();
    }

    /**
     * Função para formatar as notas de uma aluno
     */
    private function formatarNota(Nota $nota): array
    {
        return [
            'id' => $nota->id,
            'periodo' => $nota->periodo,
            'mac' => $nota->mac,
            'nota_prova_professor' => $nota->nota_prova_professor,
            'nota_prova_trimestral' => $nota->nota_prova_trimestral,
            'media_trimestral' => $nota->media_trimestral,
            'media_final' => $nota->media_final,
            'faltas' => $nota->faltas,
            'situacao_trimestral' => $nota->situacao_trimestral,
            'situacao_anual' => $nota->situacao_anual,
        ];
    }
}
