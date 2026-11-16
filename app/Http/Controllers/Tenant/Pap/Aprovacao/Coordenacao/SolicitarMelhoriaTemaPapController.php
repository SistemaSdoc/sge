<?php

namespace App\Http\Controllers\Tenant\Pap\Aprovacao\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\SolicitarMelhoriaTemaPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Aprovacao\DecisaoTemaCoordenacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SolicitarMelhoriaTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaCoordenacaoService $decisaoTemaCoordenacaoService,
    ) {}

    /**
     * Apresenta o formulário para solicitar melhoria pela coordenação.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        Gate::forUser($user)->authorize('solicitarMelhoria', $grupoPap);

        return Inertia::render('tenant/pap/decisao-coordenacao/solicitar-melhoria', [
            'grupoPap' => $grupoPap->only(
                'id',
                'nome_grupo',
                'tema_grupo',
                'problema',
                'objectivo_geral',
                'objectivos_especificos'
            ),
            'turma' => $turma->only('nome'),
            'routeContext' => 'instituicao',
            'routeParams' => [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
                'grupoPap' => $grupoPap->id,
            ],
        ]);
    }

    /**
     * Regista um pedido de melhoria do tema pela coordenação.
     */
    public function store(
        SolicitarMelhoriaTemaPapRequest $request,
        GrupoPap $grupoPap
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        Gate::forUser($user)->authorize('solicitarMelhoria', $grupoPap);

        $resultado = $this->decisaoTemaCoordenacaoService->solicitarMelhoria(
            $grupoPap,
            $user,
            $request->validated()['recomendacao'],
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Não é possível solicitar melhoria neste momento.']);
        }

        return back()->with('success', 'Recomendação de melhoria enviada com sucesso.');
    }
}
