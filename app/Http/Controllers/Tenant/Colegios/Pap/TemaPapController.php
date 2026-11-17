<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ActualizarTemaPapPeloColegioRequest;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TemaPapController extends Controller
{
    /**
     * Recebe o serviço de unicidade das propostas de tema.
     */
    public function __construct(
        private readonly TemaPapUnicidadeService $temaPapUnicidadeService,
    ) {}

    /**
     * Apresenta o formulário para corrigir o tema no colégio.
     */
    public function edit(string $colegio, GrupoPap $grupoPap): Response
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless((string) $user->instituicao_id === $colegio, 403);

        Gate::forUser($user)->authorize('corrigirTema', $grupoPap);

        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        $turma = $grupoPap->turma;

        $cursoClasseTurno = $turma?->cursoClasseTurno;

        $cursoClasse = $cursoClasseTurno?->cursoClasse;

        $cursoTutelado = $cursoClasse?->cursoTutelado;

        abort_unless($turma && $cursoClasseTurno && $cursoClasse && $cursoTutelado, 404);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/tema/edit', [
            'grupoPap' => $grupoPap,
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
     * Actualiza a proposta de tema depois de uma melhoria solicitada.
     */
    public function update(
        ActualizarTemaPapPeloColegioRequest $request,
        string $colegio,
        GrupoPap $grupoPap
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless((string) $user->instituicao_id === $colegio, 403);

        Gate::forUser($user)->authorize('corrigirTema', $grupoPap);

        $validated = $request->validated();

        $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse.cursoTutelado');

        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma?->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno?->cursoClasse;
        $cursoTutelado = $cursoClasse?->cursoTutelado;

        abort_unless($turma && $cursoClasseTurno && $cursoClasse && $cursoTutelado, 404);

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        $this->temaPapUnicidadeService->validarUnicidade(
            [
                ...$validated,
                'estudo_caso' => $grupoPap->estudo_caso,
            ],
            (string) $cursoTutelado->id,
            (string) $turma->ano_lectivo_id,
            (string) $cursoClasseTurno->id,
            (string) $grupoPap->getKey(),
        );

        $grupoPap->update($validated);

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('success', 'Tema atualizado com sucesso.');
    }
}
