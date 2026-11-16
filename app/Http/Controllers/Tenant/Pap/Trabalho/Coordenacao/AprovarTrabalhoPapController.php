<?php

namespace App\Http\Controllers\Tenant\Pap\Trabalho\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\AprovarTrabalhoPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapCoordenacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AprovarTrabalhoPapController extends Controller
{
    public function __construct(
        private readonly RevisaoTrabalhoPapCoordenacaoService $revisaoTrabalhoPapCoordenacaoService,
    ) {}

    /**
     * Regista a aprovação definitiva do trabalho pela coordenação.
     */
    public function store(
        AprovarTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        Gate::forUser($user)->authorize('aprovarTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validated();

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise da coordenação.',
            ]);
        }

        $this->revisaoTrabalhoPapCoordenacaoService->aprovar(
            $trabalho,
            $user,
            $validated['comentario'] ?? null,
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho aprovado com sucesso.',
        ]);
    }
}
