<?php

namespace App\Services\Tenant\Pap\Consultas;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use Illuminate\Support\Collection;

class ListarTemasPendentesCoordenacaoService
{
    /**
     * Define o serviço de acesso aos cursos tutelados.
     */
    public function __construct(
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Lista as propostas PAP pendentes dos cursos tutelados pelo utilizador.
     */
    public function listar(User $user): Collection
    {
        $professor = $user->professor;
        $professorId = $professor?->getKey();
        $instituicaoId = $user->instituicao_id;

        if (! $professorId || ! $instituicaoId) {
            return collect();
        }

        $cursosTutelados = CursoTutelado::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->whereHas(
                'professores',
                function ($query) use ($professorId) {
                    $query->where('professor_id', $professorId)
                        ->where('coordenador', 1);
                }
            )
            ->pluck('id');

        $temasPendentes = $this->temasPendentesDosCursos($cursosTutelados);

        $this->crossTenantAccessService->vinculosVisiveisPorProfessor($user)
            ->each(function (CursoTuteladoShared $shared) use (&$temasPendentes): void {
                $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);

                if (! $tenantTutelado) {
                    return;
                }

                $temasPendentes = $temasPendentes->merge($tenantTutelado->run(
                    fn (): Collection => $this->temasPendentesDosCursos(
                        collect([$shared->curso_tutelado_tutelado_id])
                    )
                ));
            });

        return $temasPendentes->sortByDesc('created_at')->values();
    }

    /**
     * Lista os grupos com propostas pendentes nos cursos indicados.
     *
     * @param  Collection<int, int|string>  $cursoTuteladoIds
     */
    private function temasPendentesDosCursos(Collection $cursoTuteladoIds): Collection
    {
        if ($cursoTuteladoIds->isEmpty()) {
            return collect();
        }

        return GrupoPap::query()
            ->where('status_aprovacao', GrupoPap::APROVACAO_PENDENTE)
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse',
                fn ($query) => $query->whereIn('curso_tutelado_id', $cursoTuteladoIds)
            )
            ->with([
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'professor.user',
                'elementos.aluno',
            ])
            ->latest()
            ->get();
    }
}
