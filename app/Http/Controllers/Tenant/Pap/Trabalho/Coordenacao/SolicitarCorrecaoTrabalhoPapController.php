<?php

namespace App\Http\Controllers\Tenant\Pap\Trabalho\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\SolicitarCorrecaoTrabalhoPapRequest;
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

class SolicitarCorrecaoTrabalhoPapController extends Controller
{
    public function __construct(
        private readonly RevisaoTrabalhoPapCoordenacaoService $revisaoTrabalhoPapCoordenacaoService,
    ) {}

    /**
     * Regista um pedido de correcção do trabalho feito pela coordenação.
     */
    public function store(
        SolicitarCorrecaoTrabalhoPapRequest $request,
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

        Gate::forUser($user)->authorize('solicitarCorrecaoTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validated();

        $trabalho = $grupoPap->trabalhoPap;
        $params = compact(
            'instituicao',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        $this->revisaoTrabalhoPapCoordenacaoService->solicitarCorrecao(
            $trabalho,
            $user,
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'warning',
                'message' => 'Correção solicitada ao aluno.',
            ]);
    }
}
