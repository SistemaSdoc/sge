<?php

namespace App\Services\Tenant;

use App\Helpers\CentralDatabase;
use App\Models\Central\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantInstituicaoService
{
    public function listarTodas(): Collection
    {
        $tenantAtual = tenancy()->tenant;

        $tenants = CentralDatabase::connection()
            ->table('tenants')
            ->whereIn('status', ['active', 'trial'])
            ->get();

        return $tenants->map(function ($tenant) use ($tenantAtual) {
            if ($tenantAtual && $tenant->id === $tenantAtual->getTenantKey()) {
                $inst = DB::table('instituicoes')
                    ->first(['id', 'nome', 'sigla']);

                return $inst ? [
                    'id' => $inst->id,
                    'tenant_id' => $tenant->id,
                    'nome' => $inst->nome,
                    'sigla' => $inst->sigla,
                ] : null;
            }

            $tenantModel = Tenant::find($tenant->id);

            if (! $tenantModel) {
                return null;
            }

            try {
                tenancy()->initialize($tenantModel);

                $inst = DB::table('instituicoes')
                    ->first(['id', 'nome', 'sigla']);
            } finally {
                if ($tenantAtual) {
                    tenancy()->initialize($tenantAtual);
                } else {
                    tenancy()->end();
                }
            }

            if (! $inst) {
                return null;
            }

            return [
                'id' => $inst->id,
                'tenant_id' => $tenant->id,
                'nome' => $inst->nome,
                'sigla' => $inst->sigla,
            ];
        })->filter()->values();
    }
}
