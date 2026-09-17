<?php

namespace App\Http\Controllers;

use App\Models\PrazoProva;
use App\Models\Professor;
use App\Models\SubmissaoProva;
use App\Models\JustificativaNaoSubmissao;
use App\Services\PrazoNotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProfessorJustificativaController extends Controller
{
    public function __construct(
        private PrazoNotificacaoService $notificacaoService
    ) {}

    /**
     * Retorna o instituicao_id do utilizador autenticado.
     */
    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    /**
     * Exibe o formulário para justificar a não submissão.
     */
    public function create(PrazoProva $prazo)
    {
        //   Verificar que o prazo pertence à mesma instituição
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        Log::info('📝 ProfessorJustificativaController@create chamado', [
            'prazo_id'       => $prazo->id,
            'instituicao_id' => $prazo->instituicao_id,
            'user_id'        => auth()->id(),
        ]);

        $professor = $this->getProfessorAutenticado();
        if (!$professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        if ($this->jaSubmeteu($prazo, $professor)) {
            return redirect()->route('professor.provas.index')
                ->with('error', 'Você já submeteu uma prova para este prazo.');
        }

        if (!$this->professorPodeJustificar($prazo, $professor)) {
            abort(403, 'Você não está autorizado a justificar para esta disciplina.');
        }

        $justificativa = $this->buscarJustificativa($prazo, $professor);

        return Inertia::render('professores/justificativas/create', [
            'prazo' => [
                'id'          => $prazo->id,
                'titulo'      => $prazo->titulo ?? $prazo->tipo_prova,
                'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),
            ],
            'justificativa' => $justificativa ? [
                'id'           => $justificativa->id,
                'motivo'       => $justificativa->motivo,
                'status'       => $justificativa->status,
                'status_label' => $justificativa->status_label,
            ] : null,
        ]);
    }

    /**
     * Armazena ou atualiza a justificativa no banco.
     */
    public function store(Request $request, PrazoProva $prazo)
    {
        //   Verificar instituição
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            return back()->with('error', 'Este prazo não pertence à sua instituição.');
        }

        Log::info('📝 ProfessorJustificativaController@store chamado', [
            'prazo_id'       => $prazo->id,
            'instituicao_id' => $prazo->instituicao_id,
            'user_id'        => auth()->id(),
        ]);

        $request->validate([
            'motivo' => 'required|string|max:1000',
        ]);

        $professor = $this->getProfessorAutenticado();
        if (!$professor) {
            return back()->with('error', 'Perfil de professor não encontrado.');
        }

        if ($this->jaSubmeteu($prazo, $professor)) {
            return back()->with('error', 'Você já submeteu uma prova para este prazo.');
        }

        if (!$this->professorPodeJustificar($prazo, $professor)) {
            return back()->with('error', 'Você não está autorizado a justificar para esta disciplina.');
        }

        try {
            $justificativa = JustificativaNaoSubmissao::updateOrCreate(
                [
                    'prazo_prova_id' => $prazo->id,
                    'professor_id'   => $professor->id,
                ],
                [
                    'motivo'             => $request->motivo,
                    'status'             => 'pendente',
                    'data_justificativa' => now(),
                ]
            );

            $justificativa->load(['professor.user', 'prazo.disciplina', 'prazo.classe']);
            $this->notificacaoService->notificarDiretoresJustificativa($justificativa);

            Log::info('Justificativa enviada', [
                'justificativa_id' => $justificativa->id,
                'professor_id'     => $professor->id,
                'prazo_id'         => $prazo->id,
                'instituicao_id'   => $prazo->instituicao_id,
            ]);

            return redirect()->route('professor.provas.index')
                ->with('success', 'Justificativa enviada com sucesso! Aguarde a avaliação do diretor.');

        } catch (\Exception $e) {
            Log::error('Erro ao salvar justificativa', [
                'message'        => $e->getMessage(),
                'prazo_id'       => $prazo->id,
                'professor_id'   => $professor->id,
                'instituicao_id' => $prazo->instituicao_id,
            ]);

            return back()->with('error', 'Erro ao enviar justificativa. Tente novamente.');
        }
    }

    /**
     * Lista as justificativas do professor logado.
     *   Filtrado pela instituição do prazo.
     */
    public function index()
    {
        $professor = $this->getProfessorAutenticado();
        if (!$professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        $instituicaoId = $this->getInstituicaoId(); //  

        $justificativas = JustificativaNaoSubmissao::where('professor_id', $professor->id)
            ->whereHas('prazo', fn($q) => $q->where('instituicao_id', $instituicaoId))  //   FILTRO
            ->with('prazo')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($justificativa) {
                return [
                    'id'            => $justificativa->id,
                    'prazo_titulo'  => $justificativa->prazo->titulo ?? $justificativa->prazo->tipo_prova,
                    'motivo'        => $justificativa->motivo,
                    'status_label'  => $justificativa->status_label,
                    'data'          => $justificativa->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('professores/justificativas/index', [
            'justificativas' => $justificativas,
        ]);
    }

    // ============================================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ============================================================

    private function getProfessorAutenticado(): ?Professor
    {
        return Professor::where('user_id', auth()->id())->first();
    }

    private function jaSubmeteu(PrazoProva $prazo, Professor $professor): bool
    {
        return SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->exists();
    }

    private function professorPodeJustificar(PrazoProva $prazo, Professor $professor): bool
    {
        //   Verificação de instituição
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            return false;
        }

        if (is_null($prazo->disciplina_id)) {
            return true;
        }

        return DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->exists();
    }

    private function buscarJustificativa(PrazoProva $prazo, Professor $professor): ?JustificativaNaoSubmissao
    {
        return JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->first();
    }
}