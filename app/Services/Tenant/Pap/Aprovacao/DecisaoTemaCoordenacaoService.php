<?php

namespace App\Services\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Trabalho\SubmissaoTrabalhoPapService;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\DB;

class DecisaoTemaCoordenacaoService
{
    use NotificaGrupoPap;

    /**
     * Define o serviço que inicializa o trabalho após a aprovação.
     */
    public function __construct(
        private readonly SubmissaoTrabalhoPapService $submissaoTrabalhoPapService,
    ) {}

    /**
     * Aprova a proposta de tema PAP.
     */
    public function aprovar(
        GrupoPap $grupoPap,
        User $coordenador,
        ?string $comentario = null,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $coordenador,
            GrupoPap::APROVACAO_APROVADO,
            $comentario,
            $actorTenantId,
        );
    }

    /**
     * Reprova a proposta de tema PAP.
     */
    public function reprovar(
        GrupoPap $grupoPap,
        User $coordenador,
        string $motivo,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $coordenador,
            GrupoPap::APROVACAO_REPROVADO,
            $motivo,
            $actorTenantId,
        );
    }

    /**
     * Solicita melhorias à proposta de tema PAP.
     */
    public function solicitarMelhoria(
        GrupoPap $grupoPap,
        User $coordenador,
        string $recomendacao,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $coordenador,
            GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
            $recomendacao,
            $actorTenantId,
        );
    }

    /**
     * Actualiza o estado do tema e regista a decisão da coordenação.
     */
    private function alterarEstado(
        GrupoPap $grupoPap,
        User $coordenador,
        string $novoEstado,
        ?string $comentario = null,
        ?string $actorTenantId = null,
    ): bool {
        if (! $grupoPap->podeSerAprovado()) {
            return false;
        }

        $currentTenantId = tenancy()->tenant?->getTenantKey();

        if ($currentTenantId === null) {
            throw new \LogicException('Não é possível aprovar um tema sem um tenant activo.');
        }

        $actorTenantId ??= (string) $currentTenantId;

        return DB::transaction(function () use (
            $grupoPap,
            $coordenador,
            $novoEstado,
            $comentario,
            $actorTenantId,
            $currentTenantId,
        ): bool {
            $estadoAnterior = $grupoPap->status_aprovacao;
            $isExternalActor = $actorTenantId !== (string) $currentTenantId;

            $grupoPap->update([
                'status_aprovacao' => $novoEstado,
                'aprovado_por_id' => $isExternalActor ? null : $coordenador->id,
                'aprovado_por_externo_id' => $isExternalActor ? $coordenador->id : null,
                'aprovado_por_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'aprovado_por_nome' => $isExternalActor ? $coordenador->nome : null,
                'data_aprovacao' => now(),
                'comentario_aprovacao' => $comentario,
                ...($novoEstado === GrupoPap::APROVACAO_APROVADO ? ['status' => 'em-andamento'] : []),
            ]);

            if ($novoEstado === GrupoPap::APROVACAO_APROVADO) {
                $this->submissaoTrabalhoPapService->inicializar($grupoPap);
            }

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $isExternalActor ? null : $coordenador->id,
                'utilizador_externo_id' => $isExternalActor ? $coordenador->id : null,
                'utilizador_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'utilizador_nome' => $isExternalActor ? $coordenador->nome : null,
                'estado_anterior' => $estadoAnterior,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivo_geral' => $grupoPap->objectivo_geral,
                'objectivos_especificos' => $grupoPap->objectivos_especificos,
                'estado_novo' => $novoEstado,
                'comentario' => $comentario,
            ]);

            $grupoPap->refresh();

            match ($novoEstado) {
                GrupoPap::APROVACAO_APROVADO => $this->notificarTemaAprovado($grupoPap),
                GrupoPap::APROVACAO_REPROVADO => $this->notificarTemaReprovado($grupoPap),
                GrupoPap::APROVACAO_MELHORIA_COORDENACAO => $this->notificarMelhoriasSolicitadas($grupoPap, 'coordenacao'),
                default => null,
            };

            return true;
        });
    }
}
