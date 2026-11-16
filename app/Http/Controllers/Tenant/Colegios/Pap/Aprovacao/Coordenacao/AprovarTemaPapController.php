<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\AprovarTemaPapRequest;
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

class AprovarTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaCoordenacaoService $decisaoTemaCoordenacaoService,
        private readonly ExecutarAcaoNoGrupoPapTutelado $executarAcaoNoGrupoPapTutelado,
    ) {}

    /**
     * Apresenta o formulário para aprovar o tema pela coordenação.
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

        abort_unless($user->can('grupopap.aprovar'), 403);

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

        return Inertia::render('tenant/pap/decisao-coordenacao/aprovar', [
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
     * Regista a aprovação do tema pela coordenação do colégio.
     */
    public function store(
        AprovarTemaPapRequest $request,
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

        abort_unless($user->can('grupopap.aprovar'), 403);

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
            fn (GrupoPap $grupoPap, string $actorTenantId) => $this->decisaoTemaCoordenacaoService->aprovar(
                $grupoPap,
                $user,
                $dados['comentario'] ?? null,
                $actorTenantId,
            ),
            $tenantTutoraId,
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Erro ao aprovar o tema.']);
        }

        return back()->with('success', 'Tema aprovado com sucesso.');
    }
}
