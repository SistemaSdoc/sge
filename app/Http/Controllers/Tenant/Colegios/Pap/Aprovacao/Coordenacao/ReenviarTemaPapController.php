<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Aprovacao\Coordenacao;

use App\Actions\Tenant\Pap\Tema\ReenviarTemaPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ReenviarTemaPapPeloColegioRequest;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReenviarTemaPapController extends Controller
{
    /**
     * Recebe a acção que valida e reenvia o tema corrigido.
     */
    public function __construct(
        private readonly ReenviarTemaPap $reenviarTemaPap,
    ) {}

    /**
     * Reenvia à coordenação o tema corrigido pelo colégio.
     */
    public function store(
        ReenviarTemaPapPeloColegioRequest $request,
        GrupoPap $grupoPap
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_if($user->hasRole('Secretario do Curso'), 403);

        $this->authorize('reenviarTema', $grupoPap);

        $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');
        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno->cursoClasse;
        $cursoTutelado = $cursoClasse->cursoTutelado;
        $colegio = $cursoTutelado->instituicaoCurso->instituicao_id;

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        if (! $this->reenviarTemaPap->handle($grupoPap, $user, $request->validated())) {
            return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['grupo' => 'Este tema não pode ser reenviado neste momento.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('success', 'Tema corrigido e reenviado para nova análise com sucesso.');
    }
}
