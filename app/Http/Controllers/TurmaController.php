<?php

namespace App\Http\Controllers;

use App\Http\Resources\Turma\TurmaResourceIndex;
use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TurmaController extends Controller
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function index()
    {
        $this->authorize('viewAny', Turma::class);

        $user = Auth::user();
        $professor = $user?->professor;

        // 🔒 Fallback seguro para instituicao_id
        $instituicaoId = $user?->instituicao_id ?? null;

        // Se não houver instituição, retorna vazio (ou redireciona, conforme regra de negócio)
        if (! $instituicaoId) {
            return Inertia::render('turmas/index', [
                'turmas' => ['data' => [], 'current_page' => 1, 'last_page' => 1],
                'anosLectivos' => $this->getAnosLectivos(),
                'anoLectivoActual' => $this->getAnoLectivoDefault(),
                'can' => ['create_turma' => false],
            ]);
        }

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->getAnoLectivoDefault();

        $query = Turma::query()
            ->whereHas(
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
                fn ($q) => $q->where('instituicoes.id', $instituicaoId)
            );

        if ($anoLectivoId) {
            $query->where('ano_lectivo_id', $anoLectivoId);
        }

        // 🔒 Permissão para criar turma (com fallback)
        $canCreateTurma = optional($user)->can('create', Turma::class) ?? false;

        // 🔒 Verificações de role com fallback
        $isSuperAdmin = optional($user)->isSuperAdmin() ?? false;
        $isDirector = optional($user)->isDirector() ?? false;

        if (! $isSuperAdmin && ! $isDirector) {
            if (! $professor) {
                return Inertia::render('turmas/index', [
                    'turmas' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                    ],
                    'anosLectivos' => $this->getAnosLectivos(),
                    'anoLectivoActual' => $anoLectivoId,
                    'can' => [
                        'create_turma' => $canCreateTurma,
                    ],
                ]);
            }

            $query->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id));
        }

        $turmas = $query->with([
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.classeTurnoDisciplinas.disciplina:id,nome',
            'anoLectivo:id,nome',
            'alunosActivos',
        ])->paginate(10);

        return Inertia::render('turmas/index', [
            'turmas' => [
                'data' => TurmaResourceIndex::collection($turmas->items())->toArray(request()),
                'current_page' => $turmas->currentPage(),
                'last_page' => $turmas->lastPage(),
            ],
            'anosLectivos' => $this->getAnosLectivos(),
            'anoLectivoActual' => $anoLectivoId,
            'can' => [
                'create_turma' => $canCreateTurma,
            ],
        ]);
    }

    public function getTurmasDisponiveis(Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $anoLectivoId = $this->getAnoLectivoDefault();

        $turmas = Turma::where('curso_classe_turno_id', $aluno->inscricao->curso_classe_turno_id)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->get()
            ->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            ]);

        return response()->json($turmas);
    }

    public function atribuirTurma(Request $request, Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $request->validate([
            'turma_id' => 'required|exists:turmas,id',
        ]);

        $anoLectivoId = $this->getAnoLectivoDefault();

        $turma = Turma::where('id', $request->turma_id)
            ->where('curso_classe_turno_id', $aluno->inscricao->curso_classe_turno_id)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->firstOrFail();

        $turmaAtual = $aluno->turmas()->wherePivot('activo', true)->first();

        if (! $turmaAtual || $turmaAtual->id !== $turma->id) {
            if ($turmaAtual) {
                $aluno->turmas()->updateExistingPivot($turmaAtual->id, [
                    'activo' => false,
                ]);
            }

            $aluno->turmas()->syncWithoutDetaching([
                $turma->id => [
                    'activo' => true,
                    'situacao' => 'activo',
                ],
            ]);
        }

        return back()->with('success', 'Turma atribuída com sucesso!');
    }

    // ========== MÉTODOS PRIVADOS DE APOIO ==========

    private function getAnoLectivoDefault()
    {
        return $this->anoLectivoResolverService->obterAnoLectivoDefault();
    }

    private function getAnosLectivos()
    {
        return AnoLectivo::query()
            ->select('id', 'nome')
            ->orderByDesc('data_inicio')
            ->get();
    }
}
