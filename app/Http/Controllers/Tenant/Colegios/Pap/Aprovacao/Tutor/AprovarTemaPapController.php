<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\AprovarTemaPapRequest;
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

class AprovarTemaPapController extends Controller
{
    public function __construct(
        private readonly DecisaoTemaTutorService $decisaoTemaTutorService,
    ) {}

    /**
     * Apresenta o formulário para encaminhar o tema à coordenação.
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

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        $this->authorize('aprovarComoTutor', $grupoPap);

        return Inertia::render('tenant/pap/decisao-tutor/enviar-para-coordenacao', [
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
     * Regista a validação do tema pelo tutor e envia-o à coordenação.
     */
    public function store(
        AprovarTemaPapRequest $request,
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

        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);

        $this->authorize('aprovarComoTutor', $grupoPap);

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        $resultado = $this->decisaoTemaTutorService->enviarParaCoordenacao(
            $grupoPap,
            $user,
            $request->validated()['comentario'] ?? null,
        );

        if (! $resultado) {
            return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['grupo' => 'Este tema não pode ser enviado à coordenação neste momento.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Tema enviado para a coordenação.',
            ]);
    }
}
