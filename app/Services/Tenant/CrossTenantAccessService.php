<?php

namespace App\Services\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\AuthorizationException;

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

    private function validarTutorAutenticado(User $tutor): void
    {
        $autenticado = auth('tenant')->user();

        if (! $autenticado || (string) $autenticado->getAuthIdentifier() !== (string) $tutor->getAuthIdentifier()) {
            throw new AuthorizationException('Utilizador tutor não autenticado.');
        }
    }
}
