<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SubmissaoProva;
use App\Models\Tenant\Turma;
use App\Services\Tenant\PrazoNotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProfessorJustificativaController extends Controller
{
    public function __construct(
        private PrazoNotificacaoService $notificacaoService
    ) {}

    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    // ============================================================
    // CRIAR / MOSTRAR FORMULÁRIO
    // ============================================================

    public function create(PrazoProva $prazo, Turma $turma)
    {
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        $professor = $this->getProfessorAutenticado();
        if (! $professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        if (! $this->professorLecionaNaTurma($prazo, $professor, $turma->id)) {
            abort(403, 'Não leciona esta turma para esta disciplina.');
        }

        // Bloqueia só se já submeteu para ESTA turma
        if ($this->jaSubmeteuNaTurma($prazo, $professor, $turma->id)) {
            return redirect()
                ->route('tenant.dashboard.professor.provas.index')
                ->with('error', "Já submeteu prova para a turma {$turma->nome} neste prazo.");
        }

        // Justificativa existente desta turma
        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $turma->id)
            ->first();

        Log::info('📝 ProfessorJustificativaController@create', [
            'prazo_id'   => $prazo->id,
            'turma_id'   => $turma->id,
            'turma_nome' => $turma->nome,
            'user_id'    => auth()->id(),
        ]);

        return Inertia::render('tenant/professores/justificativas/create', [
            'prazo' => [
                'id'          => $prazo->id,
                'titulo'      => $prazo->titulo ?? $prazo->tipo_prova,
                'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),
                'disciplina'  => $prazo->disciplina?->nome,
                'classe'      => $prazo->classe?->nome,
                'status'      => $prazo->status,
            ],
            'turma' => [
                'id'   => $turma->id,
                'nome' => $turma->nome,
            ],
            'justificativa' => $justificativa ? [
                'id'           => $justificativa->id,
                'motivo'       => $justificativa->motivo,
                'status'       => $justificativa->status,
                'status_label' => $justificativa->status_label,
            ] : null,
        ]);
    }

    // ============================================================
    // GUARDAR JUSTIFICATIVA
    // ============================================================

    public function store(Request $request, PrazoProva $prazo, Turma $turma)
    {
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            return back()->with('error', 'Este prazo não pertence à sua instituição.');
        }

        $request->validate([
            'motivo' => 'required|string|min:10|max:1000',
        ]);

        $professor = $this->getProfessorAutenticado();
        if (! $professor) {
            return back()->with('error', 'Perfil de professor não encontrado.');
        }

        if (! $this->professorLecionaNaTurma($prazo, $professor, $turma->id)) {
            return back()->with('error', 'Não leciona esta turma para esta disciplina.');
        }

        if ($this->jaSubmeteuNaTurma($prazo, $professor, $turma->id)) {
            return back()->with('error', "Já submeteu prova para a turma {$turma->nome}.");
        }

        try {
            $existente = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
                ->where('professor_id', $professor->id)
                ->where('turma_id', $turma->id)
                ->first();

            if ($existente && $existente->status !== 'pendente') {
                return back()->with('error', 'Esta justificativa já foi avaliada e não pode ser alterada.');
            }

            $justificativa = JustificativaNaoSubmissao::updateOrCreate(
                [
                    'prazo_prova_id' => $prazo->id,
                    'professor_id'   => $professor->id,
                    'turma_id'       => $turma->id,
                ],
                [
                    'motivo'             => $request->motivo,
                    'status'             => 'pendente',
                    'data_justificativa' => now(),
                ]
            );

            $justificativa->load(['professor.user', 'prazo.disciplina', 'prazo.classe', 'turma']);
            $this->notificacaoService->notificarDiretoresJustificativa($justificativa);

            Log::info('Justificativa enviada', [
                'justificativa_id' => $justificativa->id,
                'prazo_id'         => $prazo->id,
                'turma_id'         => $turma->id,
                'professor_id'     => $professor->id,
            ]);

            return redirect()
                ->route('tenant.dashboard.professor.provas.index')
                ->with('success', "Justificativa enviada para a turma {$turma->nome}.");
        } catch (\Exception $e) {
            Log::error('Erro ao salvar justificativa', [
                'message'  => $e->getMessage(),
                'prazo_id' => $prazo->id,
                'turma_id' => $turma->id,
            ]);

            return back()->with('error', 'Erro ao enviar justificativa. Tente novamente.');
        }
    }

    // ============================================================
    // LISTAR JUSTIFICATIVAS DO PROFESSOR
    // ============================================================

    public function index()
    {
        $professor = $this->getProfessorAutenticado();
        if (! $professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        $instituicaoId = $this->getInstituicaoId();

        $justificativas = JustificativaNaoSubmissao::where('professor_id', $professor->id)
            ->whereHas('prazo', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with(['prazo', 'turma'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($j) => [
                'id'           => $j->id,
                'prazo_titulo' => $j->prazo->titulo ?? $j->prazo->tipo_prova,
                'turma_nome'   => $j->turma?->nome,
                'motivo'       => $j->motivo,
                'status_label' => $j->status_label,
                'data'         => $j->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('tenant/professores/justificativas/index', [
            'justificativas' => $justificativas,
        ]);
    }

    // ============================================================
    // AUXILIARES
    // ============================================================

    private function getProfessorAutenticado(): ?Professor
    {
        return Professor::where('user_id', auth()->id())->first();
    }

    private function jaSubmeteuNaTurma(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        return SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $turmaId)
            ->exists();
    }

    private function professorLecionaNaTurma(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        $query = DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('turmas', 'curso_classe_turno.id', '=', 'turmas.curso_classe_turno_id')
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->where('turmas.id', $turmaId);

        if ($prazo->disciplina_id) {
            $query->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
        }

        return $query->exists();
    }
}