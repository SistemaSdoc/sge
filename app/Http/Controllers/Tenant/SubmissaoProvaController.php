<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SubmissaoProva;
use App\Services\Tenant\PrazoNotificacaoService;
use App\Services\Tenant\ProvaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubmissaoProvaController extends Controller
{
    protected ProvaService $provaService;

    public function __construct(
        ProvaService $provaService,
        private PrazoNotificacaoService $notificacaoService
    ) {
        $this->provaService = $provaService;
        $this->authorizeResource(SubmissaoProva::class, 'submissao');
    }

    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    // ============================================================
    // DASHBOARD DO PROFESSOR
    // ============================================================

    public function index(Request $request)
    {
        $professor = $this->getProfessorAutenticado();

        if (! $professor) {
            return $this->renderDashboardVazio();
        }

        $instituicaoId = $this->getInstituicaoId();

        $prazosAbertos    = $this->getPrazosAbertosParaProfessor($professor, $instituicaoId);
        $prazosEncerrados = $this->getPrazosEncerradosParaProfessor($professor, $instituicaoId);
        $historico        = $this->getHistoricoSubmissoes($professor, $instituicaoId);

        return Inertia::render('tenant/professores/provas/index', [
            'prazos_abertos'    => $prazosAbertos,
            'prazos_encerrados' => $prazosEncerrados,
            'historico'         => $historico,
        ]);
    }

    // ============================================================
    // SUBMETER PROVA
    // ============================================================

    public function create(Request $request, PrazoProva $prazo)
    {
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        $professor = $this->getProfessorAutenticado();
        if (! $professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        $this->verificarPermissaoSubmissao($prazo, $professor);

        // Nota: justificativa agora é por turma, não bloqueia a página globalmente
        if (! $prazo->isAberto()) {
            return redirect()->route('tenant.dashboard.professor.provas.index')
                ->with('error', 'Este prazo já está encerrado.');
        }

        $turmas = $this->getTurmasDoProfessor($prazo, $professor);
        $turmaSelecionada = $request->input('turma_id');

        return Inertia::render('tenant/professores/provas/submeter', [
            'prazo'             => $this->formatarPrazoParaSubmissao($prazo),
            'turmas'            => $turmas,
            'turma_selecionada' => $turmaSelecionada,
        ]);
    }

    public function store(Request $request, PrazoProva $prazo)
    {
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            return back()->with('error', 'Este prazo não pertence à sua instituição.');
        }

        $professor = $this->getProfessorAutenticado();
        if (! $professor) {
            return back()->with('error', 'Perfil de professor não encontrado.');
        }

        $this->verificarPermissaoSubmissao($prazo, $professor);

        if (! $prazo->isAberto()) {
            return back()->with('error', 'Este prazo já está encerrado.');
        }

        $dadosValidados = $request->validate([
            'turma_id'       => 'required|uuid|exists:turmas,id',
            'arquivo_prova'  => 'required|file|mimes:pdf,doc,docx|max:10240',
            'arquivo_chave'  => 'required|file|mimes:pdf,doc,docx|max:10240',
            'comentario'     => 'nullable|string|max:500',
        ]);

        if (! $this->professorLecionaNaTurma($prazo, $professor, $dadosValidados['turma_id'])) {
            return back()->with('error', 'Você não leciona nesta turma para esta disciplina.');
        }

        //  Bloqueia se justificativa desta TURMA foi recusada
        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $dadosValidados['turma_id'])
            ->first();

        if ($justificativa && $justificativa->status === 'recusada') {
            return back()->with('error', 'A sua justificativa para esta turma foi recusada. Não pode submeter para este prazo.');
        }

        try {
            $submissao = $this->provaService->submeterProva(
                $prazo,
                $professor->id,
                $dadosValidados['turma_id'],
                $dadosValidados['arquivo_prova'],
                $dadosValidados['arquivo_chave'],
                $dadosValidados['comentario'] ?? null
            );

            $submissao->load(['professor.user', 'prazo.disciplina', 'prazo.classe', 'turma']);
            $this->notificacaoService->notificarDiretoresNovaSubmissao($submissao);

            Log::info('Nova submissão criada', [
                'submissao_id'   => $submissao->id,
                'professor_id'   => $professor->id,
                'prazo_id'       => $prazo->id,
                'turma_id'       => $dadosValidados['turma_id'],
                'instituicao_id' => $prazo->instituicao_id,
            ]);

            return redirect()->route('tenant.dashboard.professor.provas.index')
                ->with('success', "Prova submetida com sucesso! (Versão {$submissao->versao})");
        } catch (\Exception $e) {
            Log::error('Erro ao submeter prova', [
                'prazo_id'       => $prazo->id,
                'professor_id'   => $professor->id,
                'turma_id'       => $dadosValidados['turma_id'],
                'instituicao_id' => $prazo->instituicao_id,
                'message'        => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function arquivo(SubmissaoProva $submissao, string $tipo)
    {
        $this->authorize('visualizarArquivo', $submissao);

        $path = match ($tipo) {
            'prova' => $submissao->caminho_prova,
            'chave' => $submissao->caminho_chave,
            default => abort(404),
        };

        $privateDisk = Storage::disk('local');
        if ($privateDisk->exists($path)) {
            return response()->download($privateDisk->path($path));
        }

        $legacyPath = storage_path('app/public/' . $path);
        if (is_file($legacyPath)) {
            return response()->download($legacyPath);
        }

        abort(404);
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    private function getProfessorAutenticado(): ?Professor
    {
        return Professor::where('user_id', auth()->id())->first();
    }

    private function renderDashboardVazio()
    {
        return Inertia::render('tenant/professores/provas/index', [
            'prazos_abertos'    => [],
            'prazos_encerrados' => [],
            'historico'         => SubmissaoProva::whereRaw('1 = 0')->paginate(15),
        ]);
    }

    private function getTurmasDoProfessor(PrazoProva $prazo, Professor $professor): array
    {
        $query = DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('turma_disciplina_professor.professor_id', $professor->id);

        if ($prazo->disciplina_id) {
            $query->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
        }

        if ($prazo->classe_id) {
            $query->where('curso_classe.classe_id', $prazo->classe_id);
        }

        return $query
            ->select('turmas.id', 'turmas.nome')
            ->distinct()
            ->orderBy('turmas.nome')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'nome' => $t->nome])
            ->toArray();
    }

    private function professorLecionaNaTurma(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        $query = DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->join('curso_classe_turno', 'turmas.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->where('turmas.id', $turmaId);

        if ($prazo->disciplina_id) {
            $query->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
        }

        if ($prazo->classe_id) {
            $query->where('curso_classe.classe_id', $prazo->classe_id);
        }

        return $query->exists();
    }

    private function jaSubmeteuNaTurma(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        $ultima = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $turmaId)
            ->latest('versao')
            ->first();

        return $ultima && $ultima->estado !== 'rejeitado';
    }

    // ============================================================
    // PRAZOS ABERTOS (agora com turma + justificativa por turma)
    // ============================================================

    private function getPrazosAbertosParaProfessor(Professor $professor, string $instituicaoId): array
    {
        $userId = auth()->id();

        $prazos = PrazoProva::where('status', 'aberto')
            ->where('instituicao_id', $instituicaoId)
            ->where('data_inicio', '<=', now())
            ->where('data_limite', '>=', now())
            ->where(function ($query) use ($userId) {
                $query->whereNull('disciplina_id')
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('classe_turno_disciplina')
                            ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
                            ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
                            ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                            ->whereColumn('classe_turno_disciplina.disciplina_id', '=', 'prazos_provas.disciplina_id')
                            ->where('professores.user_id', $userId)
                            ->where(function ($innerQuery) {
                                $innerQuery->whereNull('prazos_provas.classe_id')
                                    ->orWhereColumn('curso_classe.classe_id', 'prazos_provas.classe_id');
                            });
                    });
            })
            ->with(['disciplina', 'classe'])
            ->get();

        $resultados = [];

        foreach ($prazos as $prazo) {
            $turmas = $this->getTurmasDoProfessor($prazo, $professor);

            if (empty($turmas)) {
                continue;
            }

            foreach ($turmas as $turma) {
                //  Justificativa POR TURMA
                $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
                    ->where('professor_id', $professor->id)
                    ->where('turma_id', $turma['id'])
                    ->first();

                $bloqueado  = $justificativa && $justificativa->status === 'recusada';
                $jaSubmeteu = $this->jaSubmeteuNaTurma($prazo, $professor, $turma['id']);

                $resultados[] = [
                    'id'          => $prazo->id . '-' . $turma['id'],
                    'prazo_id'    => $prazo->id,
                    'titulo'      => $prazo->titulo ?? $prazo->tipo_prova,
                    'disciplina'  => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe'      => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,
                    'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),

                    'turma_id'    => $turma['id'],
                    'turma_nome'  => $turma['nome'],

                    'ja_submeteu' => $jaSubmeteu,
                    'bloqueado'   => $bloqueado,

                    'justificativa' => $justificativa ? [
                        'id'           => $justificativa->id,
                        'motivo'       => $justificativa->motivo,
                        'status'       => $justificativa->status,
                        'status_label' => $justificativa->status_label,
                    ] : null,

                    'url_submeter' => route('tenant.dashboard.professor.provas.submeter', [
                        'prazo'    => $prazo->id,
                        'turma_id' => $turma['id'],
                    ]),

                    'url_justificar' => route('tenant.dashboard.professor.justificar.create', [
                        'prazo' => $prazo->id,
                        'turma' => $turma['id'],
                    ]),
                ];
            }
        }

        return $resultados;
    }

    // ============================================================
    // PRAZOS ENCERRADOS
    // ============================================================

    private function getPrazosEncerradosParaProfessor(Professor $professor, string $instituicaoId): array
    {
        $userId = auth()->id();

        $prazos = PrazoProva::where('status', '!=', 'aberto')
            ->where('instituicao_id', $instituicaoId)
            ->where(function ($query) use ($userId) {
                $query->whereNull('disciplina_id')
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('classe_turno_disciplina')
                            ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
                            ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
                            ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                            ->whereColumn('classe_turno_disciplina.disciplina_id', '=', 'prazos_provas.disciplina_id')
                            ->where('professores.user_id', $userId)
                            ->where(function ($innerQuery) {
                                $innerQuery->whereNull('prazos_provas.classe_id')
                                    ->orWhereColumn('curso_classe.classe_id', 'prazos_provas.classe_id');
                            });
                    });
            })
            ->with(['disciplina', 'classe'])
            ->orderBy('data_limite', 'desc')
            ->get();

        $resultados = [];

        foreach ($prazos as $prazo) {
            $turmas = $this->getTurmasDoProfessor($prazo, $professor);

            if (empty($turmas)) {
                continue;
            }

            foreach ($turmas as $turma) {
                $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
                    ->where('professor_id', $professor->id)
                    ->where('turma_id', $turma['id'])
                    ->first();

                $bloqueado = $justificativa && $justificativa->status === 'recusada';

                $submeteu = SubmissaoProva::where('prazo_prova_id', $prazo->id)
                    ->where('professor_id', $professor->id)
                    ->where('turma_id', $turma['id'])
                    ->exists();

                $resultados[] = [
                    'id'          => $prazo->id . '-' . $turma['id'],
                    'prazo_id'    => $prazo->id,
                    'titulo'      => $prazo->titulo ?? $prazo->tipo_prova,
                    'disciplina'  => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe'      => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,
                    'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),
                    'status'       => $prazo->status,
                    'status_label' => $prazo->status_label,

                    'turma_id'    => $turma['id'],
                    'turma_nome'  => $turma['nome'],

                    'submeteu'  => $submeteu,
                    'bloqueado' => $bloqueado,

                    'justificativa' => $justificativa ? [
                        'id'           => $justificativa->id,
                        'motivo'       => $justificativa->motivo,
                        'status'       => $justificativa->status,
                        'status_label' => $justificativa->status_label,
                    ] : null,

                    'url_justificar' => route('tenant.dashboard.professor.justificar.create', [
                        'prazo' => $prazo->id,
                        'turma' => $turma['id'],
                    ]),
                ];
            }
        }

        return $resultados;
    }

    // ============================================================
    // HISTÓRICO
    // ============================================================

    private function getHistoricoSubmissoes(Professor $professor, string $instituicaoId)
    {
        return SubmissaoProva::where('professor_id', $professor->id)
            ->whereHas('prazo', fn ($q) => $q->where('instituicao_id', $instituicaoId))
            ->with(['prazo', 'disciplina', 'classe', 'turma'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($submissao) {
                return [
                    'id'            => $submissao->id,
                    'prazo'         => $submissao->prazo?->titulo ?? $submissao->prazo?->tipo_prova ?? 'N/A',
                    'disciplina'    => $submissao->disciplina?->nome ?? 'Todas',
                    'classe'        => $submissao->classe?->nome,
                    'turma_nome'    => $submissao->turma?->nome ?? $submissao->classe?->nome ?? 'N/A',
                    'versao'        => $submissao->versao,
                    'estado'        => $submissao->estado,
                    'estado_label'  => $submissao->estado_label,
                    'badge_class'   => $submissao->estado_badge_class,
                    'data_submissao'=> $submissao->data_submissao->format('d/m/Y H:i'),
                    'parecer'       => $submissao->parecer_diretor,
                    'url_prova'     => $submissao->url_prova,
                    'url_chave'     => $submissao->url_chave,
                ];
            });
    }

    private function verificarPermissaoSubmissao(PrazoProva $prazo, Professor $professor): void
    {
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        if (is_null($prazo->disciplina_id)) {
            return;
        }

        $leciona = DB::table('classe_turno_disciplina')
            ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
            ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
            ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
            ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
            ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
            ->where('professores.id', $professor->id)
            ->when($prazo->classe_id, fn ($query) => $query->where('curso_classe.classe_id', $prazo->classe_id))
            ->exists();

        if (! $leciona) {
            abort(403, 'Você não está autorizado a submeter para esta disciplina.');
        }
    }

    private function formatarPrazoParaSubmissao(PrazoProva $prazo): array
    {
        return [
            'id'              => $prazo->id,
            'titulo'          => $prazo->titulo ?? $prazo->tipo_prova,
            'disciplina'      => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome']) : null,
            'classe'          => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,
            'data_limite'     => $prazo->data_limite->format('d/m/Y H:i'),
            'permite_reenvio' => (bool) $prazo->permite_reenvio,
        ];
    }
}