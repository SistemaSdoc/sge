<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\RegraAvaliacao\CreateRegraAvaliacao;
use App\Actions\Tenant\RegraAvaliacao\DeleteRegraAvaliacao;
use App\Actions\Tenant\RegraAvaliacao\PrepareRegraAvaliacaoForm;
use App\Actions\Tenant\RegraAvaliacao\PrepareRegraAvaliacaoIndex;
use App\Actions\Tenant\RegraAvaliacao\UpdateRegraAvaliacao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RegraAvaliacao\StoreRegraAvaliacaoRequest;
use App\Http\Requests\Tenant\RegraAvaliacao\UpdateRegraAvaliacaoRequest;
use App\Models\Tenant\RegraAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegraAvaliacaoController extends Controller
{
    public function __construct(
        private readonly PrepareRegraAvaliacaoIndex $prepareRegraAvaliacaoIndex,
        private readonly PrepareRegraAvaliacaoForm $prepareRegraAvaliacaoForm,
        private readonly CreateRegraAvaliacao $createRegraAvaliacao,
        private readonly UpdateRegraAvaliacao $updateRegraAvaliacao,
        private readonly DeleteRegraAvaliacao $deleteRegraAvaliacao,
    ) {}

    /**
     * Mostra a lista de regras de avaliação.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RegraAvaliacao::class);

        return Inertia::render('tenant/regras-avaliacao/index', [
            'regrasAvaliacao' => $this->prepareRegraAvaliacaoIndex->handle(),
        ]);
    }

    /**
     * Mostra o formulário para criar uma nova regra.
     */
    public function create()
    {
        $this->authorize('create', RegraAvaliacao::class);

        return Inertia::render('tenant/regras-avaliacao/create', [
            ...$this->prepareRegraAvaliacaoForm->handle(),
        ]);
    }

    /**
     * Salva uma nova regra de avaliação.
     */
    public function store(StoreRegraAvaliacaoRequest $request)
    {
        $this->authorize('create', RegraAvaliacao::class);

        $this->createRegraAvaliacao->handle(
            $request->validated(),
            Auth::guard('tenant')->user()->instituicao_id,
        );

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }

    /**
     * Mostra os detalhes de uma regra de avaliação.
     */
    public function show(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('view', $regraAvaliacao);

        return Inertia::render('tenant/regras-avaliacao/show', [
            'regraAvaliacao' => $regraAvaliacao,
        ]);
    }

    /**
     * Mostra o formulário para editar uma regra existente.
     */
    public function edit(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('update', $regraAvaliacao);

        return Inertia::render('tenant/regras-avaliacao/edit', [
            ...$this->prepareRegraAvaliacaoForm->handle($regraAvaliacao),
        ]);
    }

    /**
     * Actualiza a regra de avaliação especificada.
     */
    public function update(UpdateRegraAvaliacaoRequest $request, RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('update', $regraAvaliacao);

        $this->updateRegraAvaliacao->handle($regraAvaliacao, $request->validated());

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }

    /**
     * Remove uma regra de avaliação.
     */
    public function destroy(RegraAvaliacao $regraAvaliacao)
    {
        $this->authorize('delete', $regraAvaliacao);

        $this->deleteRegraAvaliacao->handle($regraAvaliacao);

        return redirect()->route('tenant.dashboard.regras-avaliacao.index');
    }
}
