<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\TenantStatus;
use App\Events\TenantActivated;
use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Notifications\TenantPendenteNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TenantService
{
    /**
     * Lista tenants com as instituições carregadas.
     */
    public function getTenantsWithInstituicoes(LengthAwarePaginator $tenants): LengthAwarePaginator
    {
        return $tenants->through(function (Tenant $tenant): Tenant {
            return $tenant->setRelation('instituicao', $this->getInstituicao($tenant));
        });
    }

    /**
     * Obtém a instituição de um tenant.
     */
    public function getInstituicao(Tenant $tenant): ?Instituicao
    {
        if (!$tenant->instituicao_id) {
            return null;
        }

        return $tenant->run(fn (): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id));
    }

    /**
     * Obtém o usuário administrador de um tenant.
     */
    public function getTenantAdminUser(Tenant $tenant): ?User
    {
        if (!$tenant->admin_user_id) {
            return null;
        }

        return $tenant->run(fn (): ?User => User::query()->find($tenant->admin_user_id));
    }

    /**
     * Cria um novo tenant com domínio (status PENDENTE, sem BD).
     *
     * @param  array<string, mixed>  $data  Com chave 'domain'
     */
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $subdomain = $data['domain'];
            $baseDomain = env('APP_DOMAIN', 'localhost');
            $domain = "{$subdomain}.{$baseDomain}";

            $tenant = Tenant::create([
                'id' => $subdomain,
                'status' => TenantStatus::PENDING,
            ]);

            $tenant->domains()->create([
                'domain' => $domain,
            ]);

            Notification::route('mail', [
                $data['user_email'] => $data['user_nome'],
            ])->notify(new TenantPendenteNotification(
                        nomeInstituicao: $data['nome'],
                        nomeUser: $data['user_nome'],
                        subdomain: $data['domain'],
                        url: 'http://' . $tenant->id . '.' . env('APP_DOMAIN', 'localhost'),
                        sigla: $data['sigla'],
                    ));

            return $tenant->load('domains');
        });
    }

    /**
     * Guarda dados da instituição temporariamente até o tenant ser activado.
     *
     * @param  array<string, mixed>  $data  Dados da instituição e admin
     */
    public function savePendingTenantData(Tenant $tenant, array $data): void
    {
        DB::transaction(function () use ($tenant, $data) {
            PendingTenantData::create([
                'tenant_id' => $tenant->id,
                'nome' => $data['nome'],
                'sigla' => $data['sigla'],
                'tipo' => $data['tipo'],
                'status' => true,
                'user_nome' => $data['user_nome'],
                'user_email' => $data['user_email'],
            ]);
        });
    }

    /**
     * Actualiza a instituição de um tenant.
     *
     * @param  array<string, mixed>  $data  Campos a actualizar
     */
    public function updateInstituicao(Tenant $tenant, array $data): void
    {
        $instituicao = $this->getInstituicao($tenant);

        if ($instituicao) {
            $tenant->run(fn () => $instituicao->update($data));
        }
    }

    /**
     * Actualiza registo central do tenant.
     *
     * @param  array<string, mixed>  $data  Com chave 'domain'
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            if (isset($data['domain']) && $data['domain'] !== $tenant->domains->first()?->domain) {
                $tenant->domains()->delete();

                $tenant->domains()->create([
                    'domain' => $data['domain'],
                ]);
            }

            return $tenant->fresh()->load('domains');
        });
    }

    /**
     * Elimina um tenant e recursos associados.
     *
     * @throws \Exception
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            PendingTenantData::where('tenant_id', $tenant->id)->delete();

            // Apaga domínios
            $tenant->domains()->delete();

            // Apaga tenant
            return $tenant->delete();
        });
    }

    /**
     * Faz transição de status do tenant (PENDING para TRIAL, TRIAL para ACTIVE, etc).
     *
     * @throws \Exception
     */
    public function transitionStatus(Tenant $tenant, string $newStatus): void
    {
        DB::transaction(function () use ($tenant, $newStatus) {
            $oldStatus = $tenant->status->value;

            match ([$oldStatus, $newStatus]) {
                // PENDING para TRIAL ou PENDING para ACTIVE
                ['pending', 'trial'], ['pending', 'active'] => $this->activateTenant($tenant, $newStatus),

                // TRIAL para ACTIVE
                ['trial', 'active'] => $this->convertTrialToActive($tenant),

                // TRIAL para SUSPENDED
                ['trial', 'suspended'] => $this->suspendTenant($tenant),

                // ACTIVE para SUSPENDED
                ['active', 'suspended'] => $this->suspendTenant($tenant),

                // SUSPENDED para ACTIVE
                ['suspended', 'active'] => $this->reactivateTenant($tenant),

                default => null,
            };
        });
    }

    /**
     * Activa tenant e dispara event para jobs (BD, migrações, seed, instituição).
     *
     * @throws ModelNotFoundException
     */
    private function activateTenant(Tenant $tenant, string $status): void
    {
        $tenant->update([
            'status' => $status,
            'trial_ends_at' => $status === 'trial' ? now()->addDays(14) : null,
        ]);

        TenantActivated::dispatch($tenant);
    }

    /**
     * Converte tenant de TRIAL para ACTIVE.
     */
    private function convertTrialToActive(Tenant $tenant): void
    {
        $tenant->update([
            'status' => TenantStatus::ACTIVE,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Suspende um tenant (bloqueia acesso a funcionalidades).
     */
    private function suspendTenant(Tenant $tenant): void
    {
        $tenant->update([
            'status' => TenantStatus::SUSPENDED,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Reactiva um tenant suspenso.
     */
    private function reactivateTenant(Tenant $tenant): void
    {
        $tenant->update([
            'status' => TenantStatus::ACTIVE,
            'suspended_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Retorna transições de status válidas para o tenant actual.
     *
     * @return array<string, string>
     */
    public function getAvailableStatusTransitions(Tenant $tenant): array
    {
        return match ($tenant->status->value) {
            'pending' => [
                'active' => 'Activar',
                'trial' => 'Activar Período de Teste (14 dias)',
            ],
            'trial' => [
                'active' => 'Activar',
                'suspended' => 'Cancelar Teste',
            ],
            'active' => [
                'suspended' => 'Suspender',
            ],
            'suspended' => [
                'active' => 'Reactivar',
            ],
            'archived' => [],
        };
    }

    /**
     * Retorna lista de todos os statuses disponíveis.
     *
     * @return array<int, array<string, string>>
     */
    public function getAvailableStatuses(): array
    {
        return array_map(fn (TenantStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], TenantStatus::cases());
    }
}
