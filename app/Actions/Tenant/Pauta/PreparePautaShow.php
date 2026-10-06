<?php

namespace App\Actions\Tenant\Pauta;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Obtém os dados da pauta de uma turma.
 */
class PreparePautaShow
{
    public function __construct(
        private readonly PautaService $pautaService,
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Autoriza o acesso à turma e prepara os dados da pauta.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, string $turmaId, Request $request): array
    {
        $relacoes = $this->turmaRelations();
        $turma = Turma::query()->with($relacoes)->find($turmaId);

        if ($turma) {
            Gate::forUser($user)->authorize('pauta.view', $turma);

            return $this->dadosPauta($turma, $request);
        }

        $isCourseSecretary = $user->hasRole('Secretario do Curso');
        $isInstituteSecretary = $isCourseSecretary && $user->instituicao?->tipo === 'instituto';

        abort_if($isCourseSecretary && ! $isInstituteSecretary, 404);

        abort_unless($user->can('pautas.view'), 403);

        $vinculosActivos = $isInstituteSecretary
            ? $this->crossTenantAccessService->vinculosSecretariados($user)
            : CursoTuteladoShared::query()
                ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
                ->where('status', 'activo')
                ->get();

        foreach ($vinculosActivos->groupBy('tenant_tutelado_id') as $tenantId => $vinculosTenant) {
            $tenantTutelado = Tenant::query()->find($tenantId);

            if (! $tenantTutelado) {
                continue;
            }

            $dadosPauta = $tenantTutelado->run(function () use (
                $turmaId,
                $vinculosTenant,
                $request,
                $relacoes
            ): ?array {
                $turmaRemota = Turma::query()
                    ->whereKey($turmaId)
                    ->whereHas(
                        'cursoClasseTurno.cursoClasse.cursoTutelado',
                        fn ($query) => $query->whereIn(
                            'id',
                            $vinculosTenant->pluck('curso_tutelado_tutelado_id'),
                        ),
                    )
                    ->with($relacoes)
                    ->first();

                return $turmaRemota
                    ? $this->dadosPauta($turmaRemota, $request)
                    : null;
            });

            if ($dadosPauta) {
                return $dadosPauta;
            }
        }

        abort(404);
    }

    /**
     * Define as relações necessárias para gerar a pauta da turma.
     *
     * @return array<int, string>
     */
    private function turmaRelations(): array
    {
        return [
            'cursoClasseTurno.cursoClasse.cursoTutelado',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ];
    }

    /**
     * Valida o ano lectivo e prepara os dados que a página da pauta mostra.
     *
     * @return array<string, mixed>
     */
    private function dadosPauta(Turma $turma, Request $request): array
    {
        $anoLectivoId = $request->query('ano_lectivo_id', $turma->ano_lectivo_id);
        abort_if((string) $turma->ano_lectivo_id !== (string) $anoLectivoId, 404);

        $periodo = $request->query('periodo', '1');
        $perPage = min((int) $request->query('per_page', 10), 100);
        $filtro = $request->query('filtro');

        return [
            'cursoTutelado' => $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->only('id'),
            'pauta' => $this->pautaService->gerarPauta($turma, $periodo, $perPage, $filtro),
            'periodo' => $periodo,
            'filtro' => $filtro,
            'anoLectivo' => $turma->anoLectivo,
        ];
    }
}
