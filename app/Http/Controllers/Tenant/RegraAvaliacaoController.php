<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegraAvaliacao\StoreRegraAvaliacaoRequest;
use App\Http\Requests\RegraAvaliacao\UpdateRegraAvaliacaoRequest;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\RegraAvaliacao;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegraAvaliacaoController extends Controller
{
    /**
     * Mostra a lista de regras de avaliação.
     */
    public function index()
    {

        $this->authorize('viewAny', RegraAvaliacao::class);

        $regrasAvaliacao = RegraAvaliacao::with(['instituicao', 'anoLectivo', 'classe', 'nivelEnsino'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($regra) {
                return [
                    'id' => $regra->id,
                    'nome' => $regra->nome,
                    'nivelEnsino' => $regra->nivelEnsino?->nome ?? 'Todos os níveis',
                    'aplicacao' => $this->getAplicacao($regra),
                ];
            });

        return Inertia::render('tenant/regras-avaliacao/index', [
            'regrasAvaliacao' => $regrasAvaliacao,
        ]);
    }

    /**
     * Obtém o contexto de aplicação para uma regra.
     */
    private function getAplicacao($regra): string
    {
        if ($regra->classe_id && $regra->classe) {
            return $regra->classe->nome;
        }

        if ($regra->nivel_ensino_id && $regra->nivelEnsino) {
            return $regra->nivelEnsino->nome;
        }

        return 'Todas as classes';
    }

    /**
     * Mostra o formulário para criar uma nova regra.
     */
    public function create()
    {
        $this->authorize('create', RegraAvaliacao::class);

        $niveisEnsino = NivelEnsino::where('activo', 1)->orderBy('ordem')->get(['id', 'nome']);

        $classesPorNivel = Classe::select('classes.id', 'classes.nome', 'classes.ordem', 'curso_classe.nivel_ensino_id')
            ->join('curso_classe', 'classes.id', '=', 'curso_classe.classe_id')
            ->whereNotNull('curso_classe.nivel_ensino_id')
            ->distinct()
            ->orderBy('classes.ordem')
            ->get()
            ->groupBy('nivel_ensino_id')
            ->map(fn ($classes) => $classes->map->only(['id', 'nome'])->values());

        return Inertia::render('regras-avaliacao/create', [
            'niveisEnsino' => $niveisEnsino,
            'classesPorNivel' => $classesPorNivel,
        ]);
    }

    /**
     * Salva uma nova regra de avaliação.
     */
    public function store(StoreRegraAvaliacaoRequest $request)
    {
        $this->authorize('create', RegraAvaliacao::class);

        RegraAvaliacao::create([
            ...$request->validated(),
            'instituicao_id' => Auth::guard('tenant')->user()->instituicao_id,
            'ano_lectivo_id' => AnoLectivo::activo()?->id,
        ]);

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }

    /**
     * Mostra os detalhes de uma regra de avaliação.
     */
    public function show(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('view', $regraAvaliacao);

        return Inertia::render('regras-avaliacao/show', [
            'regraAvaliacao' => $regraAvaliacao,
        ]);
    }

    /**
     * Mostra o formulário para editar uma regra existente.
     */
    public function edit(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('update', $regraAvaliacao);

        $niveisEnsino = NivelEnsino::where('activo', 1)->orderBy('ordem')->get(['id', 'nome']);

        $classesPorNivel = Classe::select('classes.id', 'classes.nome', 'classes.ordem', 'curso_classe.nivel_ensino_id')
            ->join('curso_classe', 'classes.id', '=', 'curso_classe.classe_id')
            ->whereNotNull('curso_classe.nivel_ensino_id')
            ->distinct()
            ->orderBy('classes.ordem')
            ->get()
            ->groupBy('nivel_ensino_id')
            ->map(fn ($classes) => $classes->map->only(['id', 'nome'])->values());

        $regraAvaliacao->load(['classe', 'nivelEnsino']);

        return Inertia::render('regras-avaliacao/edit', [
            'regraAvaliacao' => $regraAvaliacao,
            'niveisEnsino' => $niveisEnsino,
            'classesPorNivel' => $classesPorNivel,
        ]);
    }

    /**
     * Actualiza a regra de avaliação especificada.
     */
    public function update(UpdateRegraAvaliacaoRequest $request, RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('update', $regraAvaliacao);

        $regraAvaliacao->update($request->validated());

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }

    /**
     * Remove uma regra de avaliação.
     */
    public function destroy(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('delete', $regraAvaliacao);
        $regraAvaliacao->delete();

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }
}
