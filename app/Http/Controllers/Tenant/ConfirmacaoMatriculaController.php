<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConfirmarMatriculaRequest;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ConfirmacaoMatricula;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\Tenant\ConfirmacaoMatriculaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ConfirmacaoMatriculaController extends Controller
{
    public function __construct(
        private readonly ConfirmacaoMatriculaService $confirmacaoMatriculaService,
    ) {}

    /**
     * Lista os alunos da turma atual que podem confirmar matrícula.
     */
    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Request $request
    ) {
        Gate::authorize('view', $turma);

        Gate::authorize('viewAny', ConfirmacaoMatricula::class);

        if (! $instituicao->permiteMatricula()) {
            abort(403, 'Esta instituição não está autorizada a aceder a confirmação de matrículas.');
        }

        // Buscar anos lectivos
        $anosLectivos = fn () => AnoLectivo::query()
            ->where('activo', true)
            ->orWhereDate('data_inicio', '>', now())
            ->orderBy('data_inicio')
            ->get()
            ->map(fn ($ano) => [
                'id' => $ano->id,
                'nome' => $ano->nome,
                'activo' => $ano->activo,
            ]);

        // Buscar turmas por ano (lazy loaded)
        $turmasPorAno = Inertia::optional(fn () => $request->query('ano_id')
            ? Turma::query()
                ->where('ano_lectivo_id', $request->query('ano_id'))
                ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn ($q) => $q->where('instituicao_id', $instituicao->id)
                )
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'turno' => $t->cursoClasseTurno?->turno?->nome,
                    'max_alunos' => $t->max_alunos,
                ])
            : []
        );

        // Buscar alunos por confirmar
        $alunos = $this->confirmacaoMatriculaService->listarAlunosPorConfirmarMatricula(
            turma: $turma,
            instituicaoId: $instituicao->id,
        );

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/confirmacao-matriculas/index', [
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
            ],
            'anosLectivos' => $anosLectivos,
            'turmasPorAno' => $turmasPorAno,
            'alunos' => $alunos,
            'params' => [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
            ],
        ]);
    }

    /**
     * Confirma a matrícula de um aluno no próximo ano lectivo, movendo-o para a nova turma.
     */
    public function store(
        StoreConfirmarMatriculaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {

        if (! $instituicao->permiteMatricula()) {
            return back()->with('error', 'Esta instituição não está autorizada a confirmar matrículas.');
        }
        $validated = $request->validated();

        $aluno = Aluno::findOrFail($validated['aluno_id']);
        $turmaNova = Turma::findOrFail($validated['turma_nova_id']);

        try {
            $this->confirmacaoMatriculaService->confirmarMatricula(
                $aluno,
                $turmaNova,
                $turma,
            );

            return to_route('confirmar-matriculas.index', [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
            ]);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
