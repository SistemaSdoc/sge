<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SubmissaoProva;
use App\Services\Tenant\PrazoNotificacaoService;
use App\Services\Tenant\ProvaService;
use App\Services\Tenant\ProvaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubmissaoProvaController extends Controller
{
    protected ProvaService $provaService;

    public function __construct(
        ProvaService $provaService,
        private PrazoNotificacaoService $notificacaoService,
        private ProvaStorageService $provaStorageService,
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

        Log::info('SubmissaoProva@index', [
            'user_id' => auth()->id(),
            'professor_id' => $professor?->id,
            'instituicao_id_user' => auth()->user()->instituicao_id,
            'total_prazos_abertos_db' => PrazoProva::where('status', 'aberto')->count(),
            'total_prazos_instituicao' => PrazoProva::where('status', 'aberto')
                ->where('instituicao_id', auth()->user()->instituicao_id)
                ->count(),
        ]);

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
    // SUBMETER PROVA — FORM
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

        $turmaId = $request->input('turma_id');

        $ultima = $turmaId
            ? $this->ultimaSubmissao($prazo, $professor, $turmaId)
            : null;

        if ($ultima && $ultima->estado !== 'rejeitado') {
            return redirect()->route('tenant.dashboard.professor.provas.index')
                ->with('error', 'Já submeteu uma prova para esta turma. Aguarde avaliação ou rejeição para reenviar.');
        }

        $temExtra   = $turmaId && $this->temPrazoExtraValido($prazo, $professor, $turmaId);
        $temReenvio = $this->temJanelaReenvio($ultima);

        if (! $prazo->isAberto() && ! $temExtra && ! $temReenvio) {
            return redirect()->route('tenant.dashboard.professor.provas.index')
                ->with('error', 'Este prazo está encerrado e a sua janela de reenvio terminou.');
        }

        $turmas = $this->getTurmasDoProfessor($prazo, $professor);

        return Inertia::render('tenant/professores/provas/submeter', [
            'prazo'             => $this->formatarPrazoParaSubmissao($prazo),
            'turmas'            => $turmas,
            'turma_selecionada' => $turmaId,
            'tem_janela_reenvio' => $temReenvio,
            'prazo_reenvio_ate' => $ultima?->prazo_reenvio_ate?->format('d/m/Y H:i'),
        ]);
    }

    // ============================================================
    // SUBMETER PROVA — GUARDAR
    // ============================================================

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

        $dadosValidados = $request->validate([
            'turma_id'      => 'required|uuid|exists:turmas,id',
            'arquivo_prova' => 'required|file|mimes:pdf,doc,docx|max:51200',   // 50 MB
            'arquivo_chave' => 'required|file|mimes:pdf,doc,docx|max:51200',   // 50 MB
            'comentario'    => 'nullable|string|max:500',
        ]);

        $ultima = $this->ultimaSubmissao($prazo, $professor, $dadosValidados['turma_id']);

        if ($ultima && $ultima->estado !== 'rejeitado') {
            return back()->with('error', 'Já submeteu uma prova para esta turma. Aguarde avaliação ou rejeição para reenviar.');
        }

        $temExtra   = $this->temPrazoExtraValido($prazo, $professor, $dadosValidados['turma_id']);
        $temReenvio = $this->temJanelaReenvio($ultima);

        if (! $prazo->isAberto() && ! $temExtra && ! $temReenvio) {
            return back()->with('error', 'Este prazo está encerrado e a sua janela de reenvio terminou.');
        }

        if (! $this->professorLecionaNaTurma($prazo, $professor, $dadosValidados['turma_id'])) {
            return back()->with('error', 'Você não leciona nesta turma para esta disciplina.');
        }

        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $dadosValidados['turma_id'])
            ->first();

        if ($justificativa && $justificativa->status === 'recusada') {
            return back()->with('error', 'A sua justificativa para esta turma foi recusada.');
        }

        try {
            $submissao = $this->provaService->submeterProva(
                $prazo,
                $professor->id,
                $dadosValidados['turma_id'],
                $dadosValidados['arquivo_prova'],
                $dadosValidados['arquivo_chave'],
                $dadosValidados['comentario'] ?? null,
                $temExtra || $temReenvio,
            );

            $submissao->load(['professor.user', 'prazo.disciplina', 'prazo.classe', 'turma']);
            $this->notificacaoService->notificarDiretoresNovaSubmissao($submissao);

            Log::info('Nova submissão criada', [
                'submissao_id'      => $submissao->id,
                'professor_id'      => $professor->id,
                'prazo_id'          => $prazo->id,
                'turma_id'          => $dadosValidados['turma_id'],
                'tem_prazo_extra'   => $temExtra,
                'tem_reenvio'       => $temReenvio,
                'encriptado'        => $submissao->encriptado,
                'comprimido'        => $submissao->comprimido,
                'tamanho_original'  => $submissao->tamanho_original_bytes,
                'tamanho_final'     => $submissao->tamanho_bytes,
                'instituicao_id'    => $prazo->instituicao_id,
            ]);

            return redirect()->route('tenant.dashboard.professor.provas.index')
                ->with('success', "Prova submetida com sucesso! (Versão {$submissao->versao})");
        } catch (\Exception $e) {
            Log::error('Erro ao submeter prova', [
                'prazo_id'     => $prazo->id,
                'professor_id' => $professor->id,
                'turma_id'     => $dadosValidados['turma_id'],
                'message'      => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    // ============================================================
    // DOWNLOAD DE ARQUIVOS (com desencriptação)
    // ============================================================

    public function arquivo(SubmissaoProva $submissao, string $tipo)
    {
        $this->authorize('visualizarArquivo', $submissao);

        $path = match ($tipo) {
            'prova' => $submissao->caminho_prova,
            'chave' => $submissao->caminho_chave,
            default => abort(404),
        };

        // O service lida com ficheiros novos (.enc) e antigos (.pdf/.doc)
        $conteudo = $this->provaStorageService->conteudo(
            $path,
            (bool) $submissao->encriptado
        );

        if ($conteudo === null) {
            Log::warning('[SubmissaoProvaController] Ficheiro não encontrado ou falha na desencriptação', [
                'submissao_id' => $submissao->id,
                'tipo'         => $tipo,
                'encriptado'   => $submissao->encriptado,
                'path'         => $path,
            ]);
            abort(404, 'Ficheiro não encontrado.');
        }

        // Nome sugerido para o download
        $extensao = $tipo === 'prova' ? 'pdf' : 'pdf';
        $nomeOriginal = "{$tipo}_v{$submissao->versao}.{$extensao}";

        Log::info('[SubmissaoProvaController] Download', [
            'submissao_id' => $submissao->id,
            'tipo'         => $tipo,
            'bytes'        => strlen($conteudo),
            'encriptado'   => $submissao->encriptado,
            'comprimido'   => $submissao->comprimido,
        ]);

        return response()->streamDownload(
            fn() => print($conteudo),
            $nomeOriginal,
            ['Content-Type' => 'application/pdf'],
        );
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

    private function temPrazoExtraValido(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        return JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $turmaId)
            ->where('status', 'aceita')
            ->whereNotNull('prazo_prorrogado_ate')
            ->where('prazo_prorrogado_ate', '>=', now())
            ->exists();
    }

    private function temJanelaReenvio(?SubmissaoProva $ultima): bool
    {
        return $ultima !== null
            && $ultima->estado === 'rejeitado'
            && $ultima->prazo_reenvio_ate !== null
            && $ultima->prazo_reenvio_ate->isFuture();
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
            ->map(fn($t) => ['id' => $t->id, 'nome' => $t->nome])
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

    private function ultimaSubmissao(PrazoProva $prazo, Professor $professor, string $turmaId): ?SubmissaoProva
    {
        return SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->where('turma_id', $turmaId)
            ->latest('versao')
            ->first();
    }

    // ============================================================
    // PRAZOS ABERTOS
    // ============================================================

    private function getPrazosAbertosParaProfessor(Professor $professor, string $instituicaoId): array
    {
        $userId = auth()->id();

        $prazos = PrazoProva::where('instituicao_id', $instituicaoId)
            ->where(function ($q) use ($professor) {
                $q->where(function ($sub) {
                    $sub->where('status', 'aberto')
                        ->where('data_inicio', '<=', now())
                        ->where('data_limite', '>=', now());
                })
                    ->orWhere(function ($sub) use ($professor) {
                        $sub->whereExists(function ($inner) use ($professor) {
                            $inner->select(DB::raw(1))
                                ->from('submissoes_provas')
                                ->whereColumn('submissoes_provas.prazo_prova_id', 'prazos_provas.id')
                                ->where('submissoes_provas.professor_id', $professor->id)
                                ->where('submissoes_provas.estado', 'rejeitado')
                                ->whereNotNull('submissoes_provas.prazo_reenvio_ate')
                                ->where('submissoes_provas.prazo_reenvio_ate', '>=', now());
                        });
                    });
            })
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
                $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
                    ->where('professor_id', $professor->id)
                    ->where('turma_id', $turma['id'])
                    ->first();

                $bloqueado = $justificativa && $justificativa->status === 'recusada';
                $ultima    = $this->ultimaSubmissao($prazo, $professor, $turma['id']);

                if ($ultima !== null && $ultima->estado !== 'rejeitado') {
                    continue;
                }

                if ($bloqueado) {
                    continue;
                }

                $foiRejeitada = $ultima?->estado === 'rejeitado';
                $temReenvio   = $this->temJanelaReenvio($ultima);

                if ($foiRejeitada && ! $temReenvio && ! $prazo->isAberto()) {
                    continue;
                }

                $resultados[] = [
                    'id'          => $prazo->id . '-' . $turma['id'],
                    'prazo_id'    => $prazo->id,
                    'titulo'      => $prazo->titulo ?? $prazo->tipo_prova,
                    'disciplina'  => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe'      => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,
                    'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),

                    'turma_id'   => $turma['id'],
                    'turma_nome' => $turma['nome'],

                    'ja_submeteu'    => false,
                    'bloqueado'      => false,
                    'foi_rejeitada'  => $foiRejeitada,
                    'motivo_reenvio' => $foiRejeitada ? 'rejeitado' : null,

                    'prazo_reenvio_ate' => $ultima?->prazo_reenvio_ate?->format('d/m/Y H:i'),
                    'dias_reenvio_restantes' => $ultima?->prazo_reenvio_ate && $ultima->prazo_reenvio_ate->isFuture()
                        ? (int) now()->diffInDays($ultima->prazo_reenvio_ate, false)
                        : null,
                    'prazo_global_fechado' => ! $prazo->isAberto(),

                    'submissao' => $ultima ? [
                        'id'              => $ultima->id,
                        'versao'          => $ultima->versao,
                        'estado'          => $ultima->estado,
                        'estado_label'    => $ultima->estado_label,
                        'parecer_diretor' => $ultima->parecer_diretor,
                        'data_submissao'  => $ultima->data_submissao?->format('d/m/Y H:i'),
                        'tamanho'         => $ultima->tamanho_formatado,
                        'tamanho_original' => $ultima->tamanho_original_formatado,
                        'reducao_percentual' => $ultima->reducao_percentual,
                        'encriptado'      => (bool) $ultima->encriptado,
                        'comprimido'      => (bool) $ultima->comprimido,
                    ] : null,

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

        $prazos = PrazoProva::where('instituicao_id', $instituicaoId)
            ->where(function ($q) {
                $q->where('status', '!=', 'aberto')
                    ->orWhere('data_limite', '<', now());
            })
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

                $ultima   = $this->ultimaSubmissao($prazo, $professor, $turma['id']);
                $submeteu = $ultima !== null;

                $temExtra = $justificativa
                    && $justificativa->status === 'aceita'
                    && $justificativa->prazo_prorrogado_ate
                    && $justificativa->prazo_prorrogado_ate->isFuture();

                $temReenvio = $this->temJanelaReenvio($ultima);

                $podeSubmeter  = false;
                $motivoReenvio = null;

                if (! $submeteu) {
                    if ($temExtra && ! $bloqueado) {
                        $podeSubmeter = true;
                    }
                } else {
                    if ($ultima->estado === 'rejeitado' && ! $bloqueado) {
                        $prazoPermiteReenvio = $prazo->isAberto() || $temExtra || $temReenvio;

                        if ($prazoPermiteReenvio) {
                            $podeSubmeter  = true;
                            $motivoReenvio = 'rejeitado';
                        }
                    }
                }

                $urlSubmeter = null;
                if ($podeSubmeter) {
                    $urlSubmeter = route('tenant.dashboard.professor.provas.submeter', [
                        'prazo'    => $prazo->id,
                        'turma_id' => $turma['id'],
                    ]);
                }

                $resultados[] = [
                    'id'           => $prazo->id . '-' . $turma['id'],
                    'prazo_id'     => $prazo->id,
                    'titulo'       => $prazo->titulo ?? $prazo->tipo_prova,
                    'disciplina'   => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe'       => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,
                    'data_limite'  => $prazo->data_limite->format('d/m/Y H:i'),
                    'status'       => $prazo->status,
                    'status_label' => $prazo->status_label,

                    'turma_id'   => $turma['id'],
                    'turma_nome' => $turma['nome'],

                    'submeteu'       => $submeteu,
                    'bloqueado'      => $bloqueado,
                    'aceite'         => $temExtra,
                    'pode_submeter'  => $podeSubmeter,
                    'motivo_reenvio' => $motivoReenvio,

                    'submissao' => $ultima ? [
                        'id'              => $ultima->id,
                        'versao'          => $ultima->versao,
                        'estado'          => $ultima->estado,
                        'estado_label'    => $ultima->estado_label,
                        'parecer_diretor' => $ultima->parecer_diretor,
                        'data_submissao'  => $ultima->data_submissao?->format('d/m/Y H:i'),
                        'tamanho'         => $ultima->tamanho_formatado,
                        'tamanho_original' => $ultima->tamanho_original_formatado,
                        'reducao_percentual' => $ultima->reducao_percentual,
                        'encriptado'      => (bool) $ultima->encriptado,
                        'comprimido'      => (bool) $ultima->comprimido,
                    ] : null,

                    'prazo_extra_ate' => $justificativa?->prazo_prorrogado_ate?->format('d/m/Y H:i'),
                    'dias_restantes'  => $justificativa?->diasRestantes(),

                    'justificativa' => $justificativa ? [
                        'id'           => $justificativa->id,
                        'motivo'       => $justificativa->motivo,
                        'status'       => $justificativa->status,
                        'status_label' => $justificativa->status_label,
                    ] : null,

                    'url_submeter'   => $urlSubmeter,
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
            ->whereHas('prazo', fn($q) => $q->where('instituicao_id', $instituicaoId))
            ->with(['prazo', 'disciplina', 'classe', 'turma'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($submissao) {
                return [
                    'id'             => $submissao->id,
                    'prazo'          => $submissao->prazo?->titulo ?? $submissao->prazo?->tipo_prova ?? 'N/A',
                    'disciplina'     => $submissao->disciplina?->nome ?? 'Todas',
                    'classe'         => $submissao->classe?->nome,
                    'turma_nome'     => $submissao->turma?->nome ?? $submissao->classe?->nome ?? 'N/A',
                    'versao'         => $submissao->versao,
                    'estado'         => $submissao->estado,
                    'estado_label'   => $submissao->estado_label,
                    'badge_class'    => $submissao->estado_badge_class,
                    'data_submissao' => $submissao->data_submissao->format('d/m/Y H:i'),
                    'parecer'        => $submissao->parecer_diretor,
                    'url_prova'      => $submissao->url_prova,
                    'url_chave'      => $submissao->url_chave,

                    // Metadata
                    'tamanho'            => $submissao->tamanho_formatado,
                    'tamanho_original'   => $submissao->tamanho_original_formatado,
                    'reducao_percentual' => $submissao->reducao_percentual,
                    'encriptado'         => (bool) $submissao->encriptado,
                    'comprimido'         => (bool) $submissao->comprimido,
                ];
            });
    }

    // ============================================================
    // PERMISSÕES
    // ============================================================

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
            ->when($prazo->classe_id, fn($query) => $query->where('curso_classe.classe_id', $prazo->classe_id))
            ->exists();

        if (! $leciona) {
            abort(403, 'Você não está autorizado a submeter para esta disciplina.');
        }
    }

    // ============================================================
    // FORMATAÇÃO
    // ============================================================

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
