<?php

namespace App\Actions\Tenant\Colegios\CursoTutelado;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;

/**
 * Obtém um curso tutelado no tenant do colégio.
 */
class ShowCursoTutelado
{
    public function __construct(private readonly CrossTenantAccessService $accessService) {}

    /**
     * Valida o vínculo central e prepara os dados para a página do colégio.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, string $colegio, string $cursoTutelado): array
    {
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $tenantColegio = Tenant::query()
            ->where('instituicao_id', $colegio)
            ->firstOrFail();
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('tenant_tutelado_id', $tenantColegio->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        $tenantColegio = $this->accessService->validarAcessoDoTutorAoColega(
            $user,
            (string) $tenantColegio->getTenantKey(),
            (string) $shared->getKey(),
        );

        $instituicaoTutor = Instituicao::query()->findOrFail($user->instituicao_id);

        return $tenantColegio->run(function () use ($instituicaoTutor, $colegio, $cursoTutelado): array {
            $colegioModel = Instituicao::query()->findOrFail($colegio);
            $curso = CursoTutelado::query()
                ->whereKey($cursoTutelado)
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegioModel->getKey()))
                ->with([
                    'instituicaoCurso.curso:id,nome,descricao',
                    'instituicaoCurso.instituicao:id,nome',
                    'cursoClasses.classe:id,nome',
                    'cursoClasses.turnos.turno:id,nome',
                    'cursoClasses.turnos' => fn ($query) => $query->with([
                        'turmas.alunos:id',
                        'turmas.cursoClasseTurno.turno:id,nome',
                        'turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
                        'classeTurnoDisciplinas.professores',
                        'classeTurnoDisciplinas',
                    ]),
                    'professores.user:id,nome',
                ])
                ->firstOrFail();

            return [
                'instituicao' => [
                    'id' => $instituicaoTutor->id,
                    'nome' => $instituicaoTutor->nome,
                ],
                'colegio' => [
                    'id' => $colegioModel->id,
                    'nome' => $colegioModel->nome,
                ],
                'cursoTutelado' => [
                    'id' => $curso->id,
                    'curso' => [
                        'id' => $curso->instituicaoCurso->curso->id,
                        'nome' => $curso->instituicaoCurso->curso->nome,
                        'descricao' => $curso->instituicaoCurso->curso->descricao,
                        'duracao_anos' => $curso->instituicaoCurso->duracao_anos,
                    ],
                    'instituicao' => [
                        'id' => $curso->instituicaoCurso->instituicao->id,
                        'nome' => $curso->instituicaoCurso->instituicao->nome,
                    ],
                    'instituicao_tutora' => [
                        'id' => $instituicaoTutor->id,
                        'nome' => $instituicaoTutor->nome,
                    ],
                    'classes' => $curso->cursoClasses->map(fn ($cursoClasse): array => [
                        'id' => $cursoClasse->id,
                        'nome' => $cursoClasse->classe->nome,
                        'turnos' => $cursoClasse->turnos->map(fn ($cursoClasseTurno) => $cursoClasseTurno->turno->nome),
                    ]),
                    'professores' => $curso->professores->map(fn ($professor): array => [
                        'id' => $professor->id,
                        'vinculo_id' => $professor->pivot->id,
                        'nome' => $professor->user?->nome,
                        'tipo' => $professor->pivot->tipo,
                    ]),
                    'contadores' => [
                        'turmas' => $curso->cursoClasses->flatMap(fn ($cursoClasse) => $cursoClasse->turnos)->flatMap(fn ($turno) => $turno->turmas)->count(),
                        'alunos' => $curso->cursoClasses
                            ->flatMap(fn ($cursoClasse) => $cursoClasse->turnos)
                            ->flatMap(fn ($turno) => $turno->turmas)
                            ->flatMap(fn ($turma) => $turma->alunos)
                            ->unique('id')
                            ->count(),
                    ],
                ],
                'anosLectivos' => AnoLectivo::all(),
            ];
        });
    }
}
