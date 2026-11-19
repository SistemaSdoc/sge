<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\QuadroHonra\AtualizarCriteriosQuadroHonraRequest;
use App\Http\Requests\Tenant\QuadroHonra\GerarQuadroHonraRequest;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\QuadroHonra as QuadroHonraModel;
use App\Models\Tenant\User;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\SincronizarQuadrosHonraTrimestrais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/** Apresenta os quadros e permite à Direcção configurar os critérios. */
class QuadroHonraController extends Controller
{
    public function __construct(
        private readonly CalcularCandidatosQuadroHonra $calcularCandidatos,
        private readonly SincronizarQuadrosHonraTrimestrais $sincronizarQuadros,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', QuadroHonraModel::class);
        $utilizador = $request->user('tenant');
        abort_unless($utilizador instanceof User, 403);
        $isAluno = $utilizador->hasRole('Aluno');
        $filtros = $request->validate([
            'periodo' => ['nullable', 'integer', 'in:1,2,3'],
        ]);

        $ultimoQuadro = QuadroHonraModel::query()
            ->where('tipo', 'geral')
            ->whereNotNull('periodo')
            ->orderByDesc('gerado_em')
            ->orderByDesc('id')
            ->first(['ano_lectivo_id', 'periodo']);

        $query = QuadroHonraModel::query()
            ->with([
                'anoLectivo:id,nome',
                'itens' => fn ($itensQuery) => $itensQuery->orderBy('posicao'),
                'itens.aluno.user:id,avatar',
            ])
            ->whereNotNull('periodo')
            ->where('tipo', 'geral');

        if ($ultimoQuadro) {
            $query->where('ano_lectivo_id', $ultimoQuadro->ano_lectivo_id);
        }

        $periodoSelecionado = isset($filtros['periodo'])
            ? (int) $filtros['periodo']
            : $ultimoQuadro?->periodo;

        if ($periodoSelecionado !== null) {
            $query->where('periodo', $periodoSelecionado);
        }

        $quadros = $query->orderByDesc('gerado_em')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (QuadroHonraModel $quadro): array => [
                'id' => $quadro->id,
                'ano_lectivo_id' => $quadro->ano_lectivo_id,
                'ano_lectivo' => $quadro->anoLectivo?->nome,
                'periodo' => $quadro->periodo,
                'media_minima' => $quadro->media_minima,
                'gerado_em' => $quadro->gerado_em?->toISOString(),
                'alunos' => $quadro->itens->map(fn ($item): array => [
                    'id' => $item->id,
                    'posicao' => $item->posicao,
                    'nome' => $item->nome_aluno,
                    'avatar_url' => $item->aluno?->user?->avatar_url,
                    'curso' => $item->curso,
                    'classe' => $item->classe,
                    'turma' => $item->turma,
                    'media' => (float) $item->media,
                ])->all(),
            ]);

        $instituicao = Instituicao::query()->findOrFail($utilizador->instituicao_id);

        return Inertia::render('tenant/quadro-honra/index', [
            'quadros' => $quadros,
            'criterios' => [
                'media_minima' => (float) $instituicao->quadro_honra_media_minima,
                'limite_alunos' => (int) $instituicao->quadro_honra_limite_alunos,
            ],
            'filtros' => [
                'periodo' => $periodoSelecionado === null ? '' : (string) $periodoSelecionado,
            ],
            'can' => ['ajustarCriterios' => $utilizador->can('updateCriteria', QuadroHonraModel::class)],
            'userRole' => $isAluno ? 'aluno' : 'staff',
        ]);
    }

    public function atualizarCriterios(AtualizarCriteriosQuadroHonraRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $utilizador = $request->user('tenant');
        abort_unless($utilizador instanceof User && $utilizador->instituicao_id !== null, 403);

        $instituicao = Instituicao::query()->findOrFail($utilizador->instituicao_id);
        $instituicao->update([
            'quadro_honra_media_minima' => $dados['media_minima'],
            'quadro_honra_limite_alunos' => $dados['limite_alunos'],
        ]);

        $anoLectivo = AnoLectivo::activo();

        if ($anoLectivo) {
            $this->sincronizarQuadros->recalcularUltimoTrimestreGerado(
                $utilizador->instituicao_id,
                (string) $anoLectivo->getKey(),
                $utilizador,
            );
        }

        return back()->with('success', 'Critérios actualizados. Os quadros do último trimestre gerado foram recalculados.');
    }

    /**
     * Reutiliza as regras da geração e devolve apenas os campos apresentados na pré-visualização.
     */
    public function previsualizar(GerarQuadroHonraRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $utilizador = $request->user('tenant');
        abort_unless($utilizador instanceof User && $utilizador->instituicao_id !== null, 403);

        $candidatos = $this->calcularCandidatos->calcular(
            $dados['ano_lectivo_id'],
            $utilizador->instituicao_id,
            isset($dados['media_minima']) ? (float) $dados['media_minima'] : null,
            isset($dados['limite_alunos']) ? (int) $dados['limite_alunos'] : null,
            isset($dados['periodo']) ? (int) $dados['periodo'] : 3,
        );

        return response()->json([
            // A resposta contém apenas os dados que o formulário apresenta.
            'candidatos' => $candidatos
                ->map(fn (array $candidato): array => [
                    'turma_aluno_id' => $candidato['turma_aluno_id'],
                    'nome_aluno' => $candidato['nome_aluno'],
                    'classe' => $candidato['classe'],
                    'curso' => $candidato['curso'],
                    'turma' => $candidato['turma'],
                    'media' => $candidato['media'],
                ])
                ->values(),
        ]);
    }
}
