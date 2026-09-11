<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ConfirmacaoMatricula\ConfirmarMatricula;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreConfirmarMatriculaRequest;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\ConfirmacaoMatricula;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\ConfirmacaoMatriculaViewService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ConfirmacaoMatriculaController extends Controller
{
    public function __construct(
        private readonly ConfirmacaoMatriculaViewService $confirmacaoMatriculaViewService,
        private readonly ConfirmarMatricula $confirmarMatricula,
    ) {}

    /**
     * Lista os alunos da turma actual que podem confirmar matrícula.
     */
    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        $this->validarContexto(
            $instituicao,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma
        );

        Gate::authorize('view', $turma);

        Gate::authorize('viewAny', ConfirmacaoMatricula::class);

        if (! $instituicao->permiteMatricula()) {
            abort(403, 'Esta instituição não está autorizada a aceder a confirmação de matrículas.');
        }

        $opcoes = $this->confirmacaoMatriculaViewService->opcoes($turma, $instituicao);

        $alunos = $this->confirmacaoMatriculaViewService->listarAlunos(
            turma: $turma,
            instituicaoId: $instituicao->id,
        );

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/confirmacao-matriculas/index', [
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
            ],
            'anoLectivoProximo' => $opcoes['ano'],
            'anosLectivos' => $opcoes['anos'],
            'turmasPorAno' => $opcoes['turmas'],
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
     * Confirma a matrícula de um aluno no próximo ano lectivo.
     */
    public function store(
        StoreConfirmarMatriculaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ) {
        $this->validarContexto(
            $instituicao,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma
        );

        if (! $instituicao->permiteMatricula()) {
            return back()->with('error', 'Esta instituição não está autorizada a confirmar matrículas.');
        }

        $validated = $request->validated();
        $aluno = Aluno::findOrFail($validated['aluno_id']);
        $turmaAluno = TurmaAluno::query()
            ->where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->firstOrFail();
        Gate::authorize('confirmar', $turmaAluno);
        $turmaNova = Turma::findOrFail($validated['turma_nova_id']);

        try {
            $this->confirmarMatricula->handle(
                $instituicao,
                $aluno,
                $turmaNova,
                $turma
            );

            return to_route('tenant.dashboard.confirmar-matriculas.index', [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
            ]);
        } catch (\Exception $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    private function validarContexto(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ): void {
        $turma->loadMissing('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');

        $contextoValido = (string) $turma->cursoClasseTurno?->id === (string) $cursoClasseTurno->id
            && (string) $cursoClasseTurno->curso_classe_id === (string) $cursoClasse->id
            && (string) $cursoClasse->curso_tutelado_id === (string) $cursoTutelado->id
            && (string) $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao_id === (string) $instituicao->id;

        abort_unless($contextoValido, 404);
    }
}
