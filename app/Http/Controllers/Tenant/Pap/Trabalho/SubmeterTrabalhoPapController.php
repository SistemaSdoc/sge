<?php

namespace App\Http\Controllers\Tenant\Pap\Trabalho;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\SubmeterTrabalhoPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pap\Trabalho\SubmissaoTrabalhoPapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SubmeterTrabalhoPapController extends Controller
{
    public function __construct(
        private readonly SubmissaoTrabalhoPapService $submissaoTrabalhoPapService,
    ) {}

    /**
     * Recebe uma nova versão do trabalho submetida pelo aluno.
     */
    public function store(
        SubmeterTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): RedirectResponse {
        $user = Auth::guard('tenant')->user();
        Gate::forUser($user)->authorize('submeterTrabalho', $grupoPap);
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

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['ficheiro' => 'O trabalho não pode ser submetido neste momento.']);
        }

        $this->submissaoTrabalhoPapService->submeter(
            $trabalho,
            $user,
            $validated['ficheiro'],
        );

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Trabalho submetido com sucesso. Aguarda revisão do professor tutor.',
            ]);
    }
}
