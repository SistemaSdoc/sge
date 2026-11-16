<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\SolicitarMelhoriaTemaPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Aprovacao\DecisaoTemaTutorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SolicitarMelhoriaTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaTutorService $decisaoTemaTutorService,
    ) {}

    /**
     * Apresenta ao tutor o formulário para solicitar melhoria do tema.
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
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);

        return Inertia::render('tenant/pap/decisao-tutor/solicitar-melhoria', [
            'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'tema_grupo', 'problema', 'objectivos'),
            'turma' => $turma->only('nome'),
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
     * Regista um pedido de melhoria do tema feito pelo tutor.
     */
    public function store(
        SolicitarMelhoriaTemaPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);

        $resultado = $this->decisaoTemaTutorService->solicitarMelhoria(
            $grupoPap,
            $user,
            $request->validated()['recomendacao'],
        );

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Este tema não pode receber melhorias neste momento.']);
        }

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'warning',
                'message' => 'Melhoria solicitada aos alunos.',
            ]);
    }
}
