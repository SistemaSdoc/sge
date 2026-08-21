<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    /**
     * List tenants with the institution stored in each tenant database.
     */
    public function getTenantsWithInstituicoes(LengthAwarePaginator $tenants): LengthAwarePaginator
    {
        return $tenants->through(function (Tenant $tenant): Tenant {
            return $tenant->setRelation('instituicao', $this->getInstituicao($tenant));
        });
    }

    /**
     * Get the institution referenced by the tenant central record.
     */
    public function getInstituicao(Tenant $tenant): ?Instituicao
    {
        if (! $tenant->instituicao_id) {
            return null;
        }

        return $tenant->run(fn (): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id));
    }

    /**
     * Create and associate the institution inside the tenant database.
     *
     * @param  array<string, mixed>  $data
     */
    public function createInstituicao(Tenant $tenant, array $data): Instituicao
    {
        $instituicao = $tenant->run(function () use ($data, $tenant): Instituicao {
            $instituicao = Instituicao::create([
                'nome' => $data['nome'],
                'sigla' => $data['sigla'],
                'tipo' => $data['tipo'],
                'email' => $data['email'],
                'telefone' => $data['telefone'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'endereco' => $data['endereco'] ?? null,
                'status' => $data['status'] ?? true,
                'tenant_id' => $tenant->id,
            ]);

            $user = User::create([
                'nome' => $data['user_nome'],
                'email' => $data['user_email'],
                'password' => Hash::make('12345678'),
                'instituicao_id' => $instituicao->id,
            ]);

            $user->assignRole('Director');

            return $instituicao;
        });

        $tenant->update(['instituicao_id' => $instituicao->id]);

        return $instituicao;
    }

    /**
     * Update the institution associated with a tenant.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateInstituicao(Tenant $tenant, array $data): void
    {
        $instituicao = $this->getInstituicao($tenant);

        if ($instituicao) {
            $tenant->run(fn () => $instituicao->update($data));
        }
    }

    /**
     * Create a new tenant with domain.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTenant(array $data): Tenant
    {
        $subdomain = $data['domain'];
        $baseDomain = env('APP_DOMAIN', 'localhost');
        $domain = "{$subdomain}.{$baseDomain}";

        $tenant = Tenant::create([
            'id' => $subdomain,
        ]);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        return $tenant->load('domains');
    }

    /**
     * Update an existing tenant.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        if (isset($data['domain']) && $data['domain'] !== $tenant->domains->first()?->domain) {
            $tenant->domains()->delete();

            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);
        }

        return $tenant->fresh()->load('domains');
    }

    /**
     * Delete a tenant and its associated resources.
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        $tenant->domains()->delete();

        return $tenant->delete();
    }

    /**
     * Toggle the status of a tenant between ACTIVE and INACTIVE.
     */
    public function toggleStatus(Tenant $tenant): Tenant
    {
        $newStatus = $tenant->status === TenantStatus::ACTIVE
            ? TenantStatus::INACTIVE
            : TenantStatus::ACTIVE;

        $tenant->update(['status' => $newStatus]);

        return $tenant->fresh();
    }
}
