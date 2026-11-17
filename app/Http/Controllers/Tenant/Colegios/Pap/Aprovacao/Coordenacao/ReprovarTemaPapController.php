<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ReprovarTemaPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Aprovacao\DecisaoTemaCoordenacaoService;
use App\Services\Tenant\Pap\Contexto\ExecutarAcaoNoGrupoPapTutelado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReprovarTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaCoordenacaoService $decisaoTemaCoordenacaoService,
        private readonly ExecutarAcaoNoGrupoPapTutelado $executarAcaoNoGrupoPapTutelado,
    ) {}

    /**
     * Apresenta o formulário para reprovar o tema pela coordenação.
     */
    public function create(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_if($user->hasRole('Secretario do Curso'), 403);

        abort_unless($user->can('grupopap.reprovar'), 403);

        $tenantTutoraId = (string) tenancy()->tenant->getTenantKey();

        $proposta = $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            (string) $cursoTutelado->getKey(),
            (string) $cursoClasse->getKey(),
            (string) $cursoClasseTurno->getKey(),
            (string) $turma->getKey(),
            (string) $grupoPap->getKey(),
            $user,
            function (GrupoPap $grupoPap, string $actorTenantId): array {
                abort_unless($grupoPap->podeSerAprovado(), 403);

                return [
                    'grupoPap' => $grupoPap->only(
                        'id',
                        'nome_grupo',
                        'tema_grupo',
                        'problema',
                        'objectivo_geral',
                        'objectivos_especificos',
                    ),
                    'turma' => $grupoPap->turma?->only('nome'),
                ];
            },
            $tenantTutoraId,
        );

        return Inertia::render('tenant/pap/decisao-coordenacao/reprovar', [
            ...$proposta,
            'routeContext' => 'colegio',
            'routeParams' => [
                'colegio' => $colegio,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
                'grupoPap' => $grupoPap->id,
            ],
        ]);
    }

    /**
     * Regista a reprovação do tema pela coordenação do colégio.
     */
    public function store(
        ReprovarTemaPapRequest $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_if($user->hasRole('Secretario do Curso'), 403);

        abort_unless($user->can('grupopap.reprovar'), 403);

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        $dados = $request->validated();

        $tenantTutoraId = (string) tenancy()->tenant->getTenantKey();

        $resultado = $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupoPap, string $actorTenantId) => $this->decisaoTemaCoordenacaoService->reprovar(
                $grupoPap,
                $user,
                $dados['motivo'],
                $actorTenantId,
            ),
            $tenantTutoraId,
        );

        if (! $resultado) {
            return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['grupo' => 'Este tema não pode ser reprovado neste momento.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('success', 'Tema reprovado com sucesso.');
    }
}
