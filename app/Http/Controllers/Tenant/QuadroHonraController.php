<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Enums\TipoQuadroHonra;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\QuadroHonra\GerarQuadroHonraRequest;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\QuadroHonra as QuadroHonraModel;
use App\Models\Tenant\User;
use App\Services\Tenant\QuadroHonra\QuadroHonraPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/** Entrega a página, recebe gerações e autoriza a exportação dos quadros. */
class QuadroHonraController extends Controller
{
    public function __construct(
        private readonly GerarQuadroHonra $gerarQuadroHonra,
        private readonly QuadroHonraPdfService $quadroHonraPdfService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', QuadroHonraModel::class);
        $utilizador = $request->user('tenant');
        abort_unless($utilizador instanceof User, 403);
        $filtros = $request->validate([
            'ano_lectivo_id' => ['nullable', 'uuid'],
            'tipo' => ['nullable', Rule::in(array_map(
                fn (TipoQuadroHonra $tipo): string => $tipo->value,
                TipoQuadroHonra::cases(),
            ))],
        ]);

        $query = QuadroHonraModel::query()
            ->with([
                'anoLectivo:id,nome',
                'cursoTutelado.instituicaoCurso.curso:id,nome',
                'geradoPor:id,nome',
            ])
            ->withCount('itens')
            ->where(function ($query) use ($utilizador): void {
                if ($utilizador->hasRole('Director')) {
                    $query->where('tipo', TipoQuadroHonra::GERAL->value)
                        ->orWhere(function ($cursoQuery) use ($utilizador): void {
                            $cursoQuery->where('tipo', TipoQuadroHonra::CURSO->value)
                                ->whereHas('cursoTutelado.instituicaoCurso', fn ($instituicaoQuery) => $instituicaoQuery->where('instituicao_id', $utilizador->instituicao_id)
                                );
                        });

                    return;
                }

                $query->where('tipo', TipoQuadroHonra::CURSO->value)
                    ->whereHas('cursoTutelado.instituicaoCurso', fn ($instituicaoQuery) => $instituicaoQuery->where('instituicao_id', $utilizador->instituicao_id)
                    );

                if ($utilizador->hasRole('Secretario do Curso')) {
                    $cursosIds = $utilizador->cursosSecretariados()->select('curso_tutelado.id');
                    $query->whereIn('curso_tutelado_id', $cursosIds);
                }
            });

        if (isset($filtros['ano_lectivo_id'])) {
            $query->where('ano_lectivo_id', $filtros['ano_lectivo_id']);
        }

        if (isset($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        $quadros = $query->orderByDesc('gerado_em')
            ->paginate(20)
            ->through(fn (QuadroHonraModel $quadro): array => [
                'id' => $quadro->id,
                'ano_lectivo_id' => $quadro->ano_lectivo_id,
                'ano_lectivo' => $quadro->anoLectivo?->nome,
                'tipo' => $quadro->tipo->value,
                'curso' => $quadro->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'media_minima' => $quadro->media_minima,
                'limite_alunos' => $quadro->limite_alunos,
                'quantidade_alunos' => $quadro->itens_count,
                'gerado_em' => $quadro->gerado_em?->toISOString(),
                'gerado_por' => $quadro->geradoPor?->nome,
                'pdf_url' => route('tenant.dashboard.quadro-honra.exportar', $quadro),
            ]);

        $cursosQuery = CursoTutelado::query()
            ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $utilizador->instituicao_id))
            ->with('instituicaoCurso.curso:id,nome');

        if ($utilizador->hasRole('Secretario do Curso')) {
            $cursosIds = $utilizador->cursosSecretariados()->select('curso_tutelado.id');
            $cursosQuery->whereIn('id', $cursosIds);
        }

        $cursos = $cursosQuery->get()
            ->map(fn (CursoTutelado $curso): array => [
                'id' => $curso->id,
                'nome' => $curso->instituicaoCurso?->curso?->nome ?? 'Curso',
            ])
            ->sortBy('nome')
            ->values();

        return Inertia::render('tenant/quadro-honra/index', [
            'quadros' => $quadros,
            'anosLectivos' => AnoLectivo::query()->orderByDesc('data_inicio')->get(['id', 'nome']),
            'anoAtivoId' => AnoLectivo::activo()?->id,
            'cursos' => $cursos,
            'filtros' => [
                'ano_lectivo_id' => $filtros['ano_lectivo_id'] ?? '',
                'tipo' => $filtros['tipo'] ?? '',
            ],
            'can' => [
                'gerarGeral' => $utilizador->hasRole('Director'),
                'gerarCurso' => $utilizador->hasAnyRole(['Director', 'Secretaria']),
            ],
        ]);
    }

    public function gerar(GerarQuadroHonraRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $this->authorize('generate', [
            QuadroHonraModel::class,
            $dados['tipo'],
            $dados['curso_tutelado_id'] ?? null,
        ]);

        $utilizador = Auth::guard('tenant')->user();
        abort_unless($utilizador instanceof User, 403);

        $quadro = $this->gerarQuadroHonra->handle($dados, $utilizador);

        return back()->with('success', "Quadro de Honra gerado com {$quadro->itens()->count()} aluno(s).");
    }

    public function exportar(QuadroHonraModel $quadroHonra): Response
    {
        $this->authorize('export', $quadroHonra);

        return $this->quadroHonraPdfService->download($quadroHonra);
    }
}
