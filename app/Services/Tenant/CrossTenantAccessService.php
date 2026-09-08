<?php

namespace App\Services\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class CrossTenantAccessService
{
    /**
     * Valida o acesso do tutor ao tenant que mantém o curso tutelado.
     *
     * Esta validação ocorre antes de qualquer troca de contexto de tenancy.
     */
    public function validarAcessoDoTutorAoColega(
        User $tutor,
        string $colegioTenantId,
        string $cursoTuteladoSharedId
    ): Tenant {
        $tenantTutor = tenancy()->tenant;

        $this->validarTutorAutenticado($tutor);

        if (! $tenantTutor) {
            throw new AuthorizationException('Não existe um tenant tutor activo.');
        }

        $vinculo = CursoTuteladoShared::query()
            ->whereKey($cursoTuteladoSharedId)
            ->where('status', 'activo')
            ->first();

        if (! $vinculo) {
            throw new AuthorizationException('Tutela encerrada ou inactiva.');
        }

        if ((string) $vinculo->tenant_tutor_id !== (string) $tenantTutor->getTenantKey()) {
            throw new AuthorizationException('Não és o tutor deste curso.');
        }

        if ((string) $vinculo->tenant_tutelado_id !== $colegioTenantId) {
            throw new AuthorizationException('O colégio não corresponde ao vínculo.');
        }

        return Tenant::query()->findOrFail($colegioTenantId);
    }

    /**
     * Devolve os vínculos activos dos cursos coordenados pelo professor.
     *
     * A coordenação é verificada no tenant tutor, onde o professor existe.
     */
    public function vinculosCoordenados(User $tutor): Collection
    {
        $this->validarTutorAutenticado($tutor);

        $tenantTutorId = (string) tenancy()->tenant?->getTenantKey();
        $instituicaoId = (string) $tutor->instituicao_id;
        $professorId = $tutor->professor?->getKey();

        if (! $tenantTutorId || ! $instituicaoId || ! $professorId) {
            return collect();
        }

        $cursoIds = CursoTutelado::query()
            ->where('tipo_tutela', 'propria')
            ->whereHas(
                'instituicaoCurso',
                fn ($query) => $query
                    ->where('instituicao_id', $instituicaoId)
            )
            ->whereHas(
                'professores',
                fn ($query) => $query
                    ->where('professor_id', $professorId)
                    ->where('coordenador', true)
            )
            ->with('instituicaoCurso:id,curso_id')
            ->get()
            ->pluck('instituicaoCurso.curso_id')
            ->filter()
            ->unique()
            ->values();

        if ($cursoIds->isEmpty()) {
            return collect();
        }

        return CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('status', 'activo')
            ->whereIn('curso_id', $cursoIds)
            ->get();
    }

    /**
     * Devolve os vínculos que o utilizador pode consultar no painel PAP.
     *
     * O director do instituto tem visão institucional completa; professores
     * ficam limitados aos cursos onde são coordenadores.
     */
    public function vinculosVisiveisNoPap(User $user): Collection
    {
        if ($user->hasRole('Director') && $user->instituicao?->tipo === 'instituto') {
            return CursoTuteladoShared::query()
                ->where('tenant_tutor_id', (string) tenancy()->tenant->getTenantKey())
                ->where('status', 'activo')
                ->get();
        }

        return $this->vinculosCoordenados($user);
    }

    /**
     * Valida que o grupo PAP existe no tenant tutelado e pertence ao vínculo.
     *
     * A autorização do tutor deve ser executada antes de entrar no tenant do
     * colégio; dentro do callback apenas são verificadas relações locais.
     */
    public function validarAcessoAoGrupoPap(
        User $tutor,
        Tenant $tenantColega,
        string $grupoPapId,
        string $cursoTuteladoSharedId
    ): void {
        $vinculo = CursoTuteladoShared::query()
            ->whereKey($cursoTuteladoSharedId)
            ->where('status', 'activo')
            ->first();

        if (! $vinculo || (string) $vinculo->tenant_tutelado_id !== (string) $tenantColega->getTenantKey()) {
            throw new AuthorizationException('O vínculo de tutela não corresponde ao colégio.');
        }

        $tenantValidado = $this->validarAcessoDoTutorAoColega(
            $tutor,
            (string) $tenantColega->getTenantKey(),
            $cursoTuteladoSharedId,
        );

        if ((string) $tenantValidado->getTenantKey() !== (string) $tenantColega->getTenantKey()) {
            throw new AuthorizationException('Tenant tutelado inválido.');
        }

        $this->validarCoordenacaoDoCurso($tutor, $vinculo);

        $tenantColega->run(function () use ($grupoPapId, $vinculo): void {
            $grupo = GrupoPap::query()
                ->with('turma.cursoClasseTurno.cursoClasse.cursoTutelado')
                ->findOrFail($grupoPapId);

            $cursoTutelado = $grupo->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado;

            if (! $cursoTutelado
                || $cursoTutelado->tipo_tutela !== 'externa'
                || (string) $cursoTutelado->curso_tutelado_shared_id !== (string) $vinculo->getKey()
                || (string) $cursoTutelado->getKey() !== (string) $vinculo->curso_tutelado_tutelado_id
            ) {
                throw new AuthorizationException('O grupo não pertence ao curso tutelado.');
            }
        });
    }

    /**
     * Garante que o actor coordena o mesmo curso central da tutela.
     */
    private function validarCoordenacaoDoCurso(User $tutor, CursoTuteladoShared $vinculo): void
    {
        if (! $vinculo->curso_id || ! $tutor->professor) {
            throw new AuthorizationException('O professor não está associado ao curso tutor.');
        }

        $autorizado = CursoTutelado::query()
            ->whereHas(
                'instituicaoCurso',
                fn ($query) => $query
                    ->where('instituicao_id', $tutor->instituicao_id)
                    ->where('curso_id', $vinculo->curso_id)
            )
            ->whereHas(
                'professores',
                fn ($query) => $query
                    ->where('professor_id', $tutor->professor->getKey())
                    ->where('coordenador', true)
            )
            ->exists();

        if (! $autorizado) {
            throw new AuthorizationException('O professor não coordena este curso.');
        }
    }

    private function validarTutorAutenticado(User $tutor): void
    {
        $autenticado = auth('tenant')->user();

        if (! $autenticado || (string) $autenticado->getAuthIdentifier() !== (string) $tutor->getAuthIdentifier()) {
            throw new AuthorizationException('Utilizador tutor não autenticado.');
        }
    }
}
