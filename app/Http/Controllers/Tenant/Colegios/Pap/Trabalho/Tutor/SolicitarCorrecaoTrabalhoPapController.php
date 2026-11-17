<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\SolicitarCorrecaoTrabalhoPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapTutorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SolicitarCorrecaoTrabalhoPapController extends Controller
{
    public function __construct(
        private readonly RevisaoTrabalhoPapTutorService $revisaoTrabalhoPapTutorService,
    ) {}

    /**
     * Regista um pedido de correcção do trabalho feito pelo tutor.
     */
    public function store(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        $this->authorize('solicitarCorrecaoTrabalhoComoTutor', $grupoPap);

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        $validated = $request->validated();

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['trabalho' => 'O trabalho não está disponível para análise do tutor.']);
        }

        $this->revisaoTrabalhoPapTutorService->solicitarCorrecao(
            $trabalho,
            Auth::guard('tenant')->user(),
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'warning',
                'message' => 'Correção solicitada ao aluno.',
            ]);
    }
}
