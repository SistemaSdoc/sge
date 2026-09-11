<?php

namespace App\Http\Controllers;

use App\Models\PrazoProva;
use App\Models\SubmissaoProva;
use App\Models\Professor;
use App\Services\ProvaService;
use App\Models\JustificativaNaoSubmissao;
use App\Services\PrazoNotificacaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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


    /**
     * Retorna o instituicao_id do utilizador autenticado.
     */
    private function getInstituicaoId(): ?string
    {
        return auth()->user()->instituicao_id;
    }

    /**
     * Dashboard do professor: prazos abertos, encerrados e histórico.
     */
    public function index(Request $request)
    {
        $professor = $this->getProfessorAutenticado();

        if (!$professor) {
            return $this->renderDashboardVazio();
        }

        $instituicaoId = $this->getInstituicaoId(); //  

        $prazosAbertos = $this->getPrazosAbertosParaProfessor($professor, $instituicaoId);
        $prazosEncerrados = $this->getPrazosEncerradosParaProfessor($professor, $instituicaoId);
        $historico = $this->getHistoricoSubmissoes($professor, $instituicaoId);

        return Inertia::render('professores/provas/index', [
            'prazos_abertos'    => $prazosAbertos,
            'prazos_encerrados' => $prazosEncerrados,
            'historico'         => $historico,
        ]);
    }

    /**
     * Exibe o formulário para submeter uma prova.
     */
    public function create(Request $request, PrazoProva $prazo)
    {
        //   Verificar que o prazo pertence à mesma instituição
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        $professor = $this->getProfessorAutenticado();

        if (!$professor) {
            abort(403, 'Perfil de professor não encontrado.');
        }

        $this->verificarPermissaoSubmissao($prazo, $professor);

        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->first();

        if ($justificativa && $justificativa->status === 'recusada') {
            return redirect()->route('professor.provas.index')
                ->with('error', 'A sua justificativa foi recusada. Não pode submeter para este prazo.');
        }

        if (!$prazo->isAberto()) {
            return redirect()->route('professor.provas.index')
                ->with('error', 'Este prazo já está encerrado.');
        }

        $turmas = $this->getTurmasDoProfessor($prazo, $professor);
        $turmaSelecionada = $request->input('turma_id');

        return Inertia::render('professores/provas/submeter', [
            'prazo'             => $this->formatarPrazoParaSubmissao($prazo),
            'turmas'            => $turmas,
            'turma_selecionada' => $turmaSelecionada,
        ]);
    }

    /**
     * Processa a submissão da prova.
     */
public function store(Request $request, PrazoProva $prazo)
{
    // Verificar instituição
    if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
        return back()->with('error', 'Este prazo não pertence à sua instituição.');
    }

    $professor = $this->getProfessorAutenticado();

    if (!$professor) {
        return back()->with('error', 'Perfil de professor não encontrado.');
    }

    $this->verificarPermissaoSubmissao($prazo, $professor);

    $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
        ->where('professor_id', $professor->id)
        ->first();

    if ($justificativa && $justificativa->status === 'recusada') {
        return back()->with('error', 'A sua justificativa foi recusada. Não pode submeter para este prazo.');
    }

    $dadosValidados = $request->validate([
        'turma_id'      => 'required|uuid|exists:turmas,id',
        'arquivo_prova' => 'required|file|mimes:pdf,doc,docx|max:10240',
        'arquivo_chave' => 'required|file|mimes:pdf,doc,docx|max:10240',
        'comentario'    => 'nullable|string|max:500',
    ]);

    if (!$this->professorLecionaNaTurma($prazo, $professor, $dadosValidados['turma_id'])) {
        return back()->with('error', 'Você não leciona nesta turma para esta disciplina.');
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

        // NOTIFICAR DIRETORES SOBRE NOVA SUBMISSÃO
        $submissao->load(['professor.user', 'prazo.disciplina', 'prazo.classe', 'turma']);
        $this->notificacaoService->notificarDiretoresNovaSubmissao($submissao);

        Log::info(' Nova submissão criada', [
            'submissao_id'   => $submissao->id,
            'professor_id'   => $professor->id,
            'prazo_id'       => $prazo->id,
            'turma_id'       => $dadosValidados['turma_id'],
            'instituicao_id' => $prazo->instituicao_id,
        ]);

        return redirect()->route('professor.provas.index')
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

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    private function getProfessorAutenticado(): ?Professor
    {
        return Professor::where('user_id', auth()->id())->first();
    }

    private function renderDashboardVazio()
    {
        return Inertia::render('professores/provas/index', [
            'prazos_abertos'    => [],
            'prazos_encerrados' => [],
            'historico'         => SubmissaoProva::whereRaw('1 = 0')->paginate(15),
        ]);
    }

    private function getTurmasDoProfessor(PrazoProva $prazo, Professor $professor)
    {
        $query = DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->where('turma_disciplina_professor.professor_id', $professor->id);

        if ($prazo->disciplina_id) {
            $query->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
        }

        return $query
            ->select('turmas.id', 'turmas.nome')
            ->distinct()
            ->orderBy('turmas.nome')
            ->get()
            ->map(fn($t) => [
                'id'   => $t->id,
                'nome' => $t->nome,
            ])
            ->toArray();
    }

    private function professorLecionaNaTurma(PrazoProva $prazo, Professor $professor, string $turmaId): bool
    {
        if (is_null($prazo->disciplina_id)) {
            return DB::table('turma_disciplina_professor')
                ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
                ->where('turma_disciplina_professor.professor_id', $professor->id)
                ->where('turmas.id', $turmaId)
                ->exists();
        }

        return DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
            ->where('turmas.id', $turmaId)
            ->exists();
    }

    /**
     *   Prazos abertos — filtrados pela instituição.
     */
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
                          ->from('disciplinas')
                          ->join('classe_turno_disciplina', 'disciplinas.id', '=', 'classe_turno_disciplina.disciplina_id')
                          ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
                          ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
                          ->whereColumn('disciplinas.id', '=', 'prazos_provas.disciplina_id')
                          ->where('professores.user_id', $userId);
                  });
        })
        ->with(['disciplina', 'classe'])
        ->get();

    $resultados = [];

    foreach ($prazos as $prazo) {
        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->first();

        $bloqueado = $justificativa && $justificativa->status === 'recusada';

        //  BASE com disciplina E classe
        $base = [
            'prazo_id'       => $prazo->id,
            'titulo'         => $prazo->titulo ?? $prazo->tipo_prova,
            'disciplina'     => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
            'classe'         => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,  //  ADICIONADO
            'data_limite'    => $prazo->data_limite->format('d/m/Y H:i'),
            'bloqueado'      => $bloqueado,
            'justificativa'  => $justificativa ? [
                'id'           => $justificativa->id,
                'motivo'       => $justificativa->motivo,
                'status'       => $justificativa->status,
                'status_label' => $justificativa->status_label,
            ] : null,
            'url_justificar' => route('professor.justificar.create', $prazo),
        ];

        // Prazo geral (sem disciplina)
        if (is_null($prazo->disciplina_id)) {
            $turmas = $this->getTurmasDoProfessor($prazo, $professor);

            if (empty($turmas)) {
                //  Sem turmas → "Todas" (nunca o nome da classe)
                $resultados[] = array_merge($base, [
                    'id'           => $prazo->id,
                    'turma_id'     => null,
                    'turma_nome'   => 'Todas',  // 🔥
                    'ja_submeteu'  => false,
                    'url_submeter' => route('professor.provas.submeter', $prazo),
                ]);
            } else {
                foreach ($turmas as $turma) {
                    $jaSubmeteu = $this->jaSubmeteuNaTurma($prazo, $professor, $turma['id']);
                    $resultados[] = array_merge($base, [
                        'id'           => $prazo->id . '-' . $turma['id'],
                        'turma_id'     => $turma['id'],
                        'turma_nome'   => $turma['nome'],  //  nome da turma
                        'ja_submeteu'  => $jaSubmeteu,
                        'url_submeter' => route('professor.provas.submeter', [
                            'prazo'    => $prazo->id,
                            'turma_id' => $turma['id'],
                        ]),
                    ]);
                }
            }
            continue;
        }

        // Prazo com disciplina
        $turmas = $this->getTurmasDoProfessor($prazo, $professor);

        if (empty($turmas)) {
            //  Sem turmas → "Todas"
            $resultados[] = array_merge($base, [
                'id'           => $prazo->id . '-default',
                'turma_id'     => null,
                'turma_nome'   => 'Todas',  //  era $prazo->classe?->nome
                'ja_submeteu'  => false,
                'url_submeter' => route('professor.provas.submeter', $prazo),
            ]);
        } else {
            foreach ($turmas as $turma) {
                $jaSubmeteu = $this->jaSubmeteuNaTurma($prazo, $professor, $turma['id']);
                $resultados[] = array_merge($base, [
                    'id'           => $prazo->id . '-' . $turma['id'],
                    'turma_id'     => $turma['id'],
                    'turma_nome'   => $turma['nome'],  //  nome da turma
                    'ja_submeteu'  => $jaSubmeteu,
                    'url_submeter' => route('professor.provas.submeter', [
                        'prazo'    => $prazo->id,
                        'turma_id' => $turma['id'],
                    ]),
                ]);
            }
        }
    }

    return $resultados;
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

    /**
     *   Prazos encerrados — filtrados pela instituição.
     */
private function getPrazosEncerradosParaProfessor(Professor $professor, string $instituicaoId): array
{
    $userId = auth()->id();

    $prazos = PrazoProva::where('status', '!=', 'aberto')
        ->where('instituicao_id', $instituicaoId)
        ->where(function ($query) use ($userId) {
            $query->whereNull('disciplina_id')
                  ->orWhereExists(function ($sub) use ($userId) {
                      $sub->select(DB::raw(1))
                          ->from('disciplinas')
                          ->join('classe_turno_disciplina', 'disciplinas.id', '=', 'classe_turno_disciplina.disciplina_id')
                          ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
                          ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
                          ->whereColumn('disciplinas.id', '=', 'prazos_provas.disciplina_id')
                          ->where('professores.user_id', $userId);
                  });
        })
        ->with(['disciplina', 'classe'])
        ->orderBy('data_limite', 'desc')
        ->get();

    $resultados = [];

    foreach ($prazos as $prazo) {
        $justificativa = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professor->id)
            ->first();

        $bloqueado = $justificativa && $justificativa->status === 'recusada';

        //  BASE com disciplina E classe
        $base = [
            'prazo_id'       => $prazo->id,
            'titulo'         => $prazo->titulo ?? $prazo->tipo_prova,
            'disciplina'     => $prazo->disciplina ? $prazo->disciplina->only(['id', 'nome', 'sigla']) : null,
            'classe'         => $prazo->classe ? $prazo->classe->only(['id', 'nome']) : null,  //  ADICIONADO
            'data_limite'    => $prazo->data_limite->format('d/m/Y H:i'),
            'status'         => $prazo->status,
            'status_label'   => $prazo->status_label,
            'bloqueado'      => $bloqueado,
            'justificativa'  => $justificativa ? [
                'id'           => $justificativa->id,
                'motivo'       => $justificativa->motivo,
                'status'       => $justificativa->status,
                'status_label' => $justificativa->status_label,
            ] : null,
            'url_justificar' => route('professor.justificar.create', $prazo),
        ];

        // Prazo geral
        if (is_null($prazo->disciplina_id)) {
            $submeteu = SubmissaoProva::where('prazo_prova_id', $prazo->id)
                ->where('professor_id', $professor->id)
                ->exists();

            $resultados[] = array_merge($base, [
                'id'         => $prazo->id,
                'turma_id'   => null,
                'turma_nome' => 'Todas',  // 🔥
                'submeteu'   => $submeteu,
            ]);
            continue;
        }

        // Prazo com disciplina
        $turmas = $this->getTurmasDoProfessor($prazo, $professor);

        if (empty($turmas)) {
            $submeteu = SubmissaoProva::where('prazo_prova_id', $prazo->id)
                ->where('professor_id', $professor->id)
                ->exists();

            $resultados[] = array_merge($base, [
                'id'         => $prazo->id . '-default',
                'turma_id'   => null,
                'turma_nome' => 'Todas',  //  era $prazo->classe?->nome
                'submeteu'   => $submeteu,
            ]);
        } else {
            foreach ($turmas as $turma) {
                $submeteu = SubmissaoProva::where('prazo_prova_id', $prazo->id)
                    ->where('professor_id', $professor->id)
                    ->where('turma_id', $turma['id'])
                    ->exists();

                $resultados[] = array_merge($base, [
                    'id'         => $prazo->id . '-' . $turma['id'],
                    'turma_id'   => $turma['id'],
                    'turma_nome' => $turma['nome'],  //  nome da turma
                    'submeteu'   => $submeteu,
                ]);
            }
        }
    }

    return $resultados;
}

    /**
     *   Histórico de submissões — filtrado pela instituição.
     */
    private function getHistoricoSubmissoes(Professor $professor, string $instituicaoId)
    {
        return SubmissaoProva::where('professor_id', $professor->id)
            ->whereHas('prazo', fn($q) => $q->where('instituicao_id', $instituicaoId))  //   FILTRO
            ->with(['prazo', 'disciplina', 'classe', 'turma'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($submissao) {
                return [
                    'id'              => $submissao->id,
                    'prazo'           => $submissao->prazo?->titulo ?? $submissao->prazo?->tipo_prova ?? 'N/A',
                    'disciplina'      => $submissao->disciplina?->nome ?? 'Todas',
                    'classe'          => $submissao->classe?->nome,
                    'turma_nome'      => $submissao->turma?->nome ?? $submissao->classe?->nome ?? 'N/A',
                    'versao'          => $submissao->versao,
                    'estado'          => $submissao->estado,
                    'estado_label'    => $submissao->estado_label,
                    'badge_class'     => $submissao->estado_badge_class,
                    'data_submissao'  => $submissao->data_submissao->format('d/m/Y H:i'),
                    'parecer'         => $submissao->parecer_diretor,
                    'url_prova'       => $submissao->url_prova,
                    'url_chave'       => $submissao->url_chave,
                ];
            });
    }

    private function verificarPermissaoSubmissao(PrazoProva $prazo, Professor $professor): void
    {
        //   Verificar instituição
        if ($prazo->instituicao_id !== $this->getInstituicaoId()) {
            abort(403, 'Este prazo não pertence à sua instituição.');
        }

        if (is_null($prazo->disciplina_id)) {
            return;
        }

        $leciona = DB::table('disciplinas')
            ->join('classe_turno_disciplina', 'disciplinas.id', '=', 'classe_turno_disciplina.disciplina_id')
            ->join('turma_disciplina_professor', 'classe_turno_disciplina.id', '=', 'turma_disciplina_professor.classe_turno_disciplina_id')
            ->join('professores', 'turma_disciplina_professor.professor_id', '=', 'professores.id')
            ->where('disciplinas.id', $prazo->disciplina_id)
            ->where('professores.id', $professor->id)
            ->exists();

        if (!$leciona) {
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