<?php

namespace App\Http\Controllers\Tenant\Pap\Aprovacao\Coordenacao;

use App\Actions\Tenant\Pap\Tema\ReenviarTemaPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ReenviarTemaPapPelaCoordenacaoRequest;
use App\Models\Tenant\GrupoPap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReenviarTemaPapController extends Controller
{
    /**
     * Recebe a acção que valida e reenvia o tema corrigido.
     */
    public function __construct(
        private readonly ReenviarTemaPap $reenviarTemaPap,
    ) {}

    /**
     * Reenvia à coordenação o tema corrigido para nova análise.
     */
    public function store(
        ReenviarTemaPapPelaCoordenacaoRequest $request,
        GrupoPap $grupoPap
    ): RedirectResponse {
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('reenviarTema', $grupoPap);

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

        if (! $this->reenviarTemaPap->handle($grupoPap, $user, $request->validated())) {
            return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['grupo' => 'Erro ao reenviar o tema.']);
        }

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
            ->with('success', 'Tema reenviado com sucesso.');
    }
}
