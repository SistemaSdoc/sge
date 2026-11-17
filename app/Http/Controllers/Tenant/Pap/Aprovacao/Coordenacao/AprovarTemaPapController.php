<?php

namespace App\Http\Controllers\Tenant\Pap\Aprovacao\Coordenacao;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AprovarTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaCoordenacaoService $decisaoTemaCoordenacaoService,
    ) {}

    /**
     * Apresenta o formulário para aprovar o tema pela coordenação.
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

        Gate::forUser($user)->authorize('aprovar', $grupoPap);

        return Inertia::render('tenant/pap/decisao-coordenacao/aprovar', [
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
     * Regista a aprovação do tema pela coordenação.
     */
    public function store(AprovarTemaPapRequest $request, GrupoPap $grupoPap): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        Gate::forUser($user)->authorize('aprovar', $grupoPap);

        $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');
        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno->cursoClasse;
        $cursoTutelado = $cursoClasse->cursoTutelado;
        $instituicao = $cursoTutelado->instituicaoCurso->instituicao_id;

        $params = compact(
            'instituicao',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        if (! $grupoPap->podeSerAprovado()) {
            return to_route(
                'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
                $params,
            )->withErrors([
                'grupo' => 'Este tema já foi finalizado e não pode ser alterado.',
            ])->with('status', 'erro');
        }

        $resultado = $this->decisaoTemaCoordenacaoService->aprovar(
            $grupoPap,
            $user,
            $request->validated()['comentario'] ?? null,
        );

        if (! $resultado) {
            return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['grupo' => 'Erro ao aprovar o tema.']);
        }

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
            ->with('success', 'Tema aprovado com sucesso.');
    }
}
