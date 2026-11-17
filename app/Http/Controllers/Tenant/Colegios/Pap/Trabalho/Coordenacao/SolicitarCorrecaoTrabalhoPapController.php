<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Trabalho\Coordenacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\SolicitarCorrecaoTrabalhoPapRequest;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Contexto\ExecutarAcaoNoGrupoPapTutelado;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapCoordenacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SolicitarCorrecaoTrabalhoPapController extends Controller
{
    public function __construct(
        private readonly RevisaoTrabalhoPapCoordenacaoService $revisaoTrabalhoPapCoordenacaoService,
        private readonly ExecutarAcaoNoGrupoPapTutelado $executarAcaoNoGrupoPapTutelado,
    ) {}

    /**
     * Regista um pedido de correcção do trabalho pela coordenação do colégio.
     */
    public function store(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
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

        $params = compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        );

        $tenantTutoraId = (string) tenancy()->tenant->getTenantKey();

        $validated = $request->validated();

        $ficheiroCorrecao = $request->file('ficheiro');

        $resultado = $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap, string $actorTenantId) use (
                $user,
                $validated,
                $ficheiroCorrecao,
            ): bool {
                $trabalho = $grupoPap->trabalhoPap;

                if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
                    return false;
                }

                $this->revisaoTrabalhoPapCoordenacaoService->solicitarCorrecao(
                    $trabalho,
                    $user,
                    $validated['comentario'],
                    $ficheiroCorrecao,
                    $actorTenantId,
                );

                return true;
            },
            $tenantTutoraId,
        );

        if (! $resultado) {
            return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
                ->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', $params)
            ->with('toast', [
                'type' => 'warning',
                'message' => 'Correção solicitada ao aluno.',
            ]);
    }
}
