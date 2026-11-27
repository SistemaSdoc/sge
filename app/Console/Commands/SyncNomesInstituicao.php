<?php

namespace App\Console\Commands;

use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use Illuminate\Console\Command;
use Throwable;

class SyncNomesInstituicao extends Command
{
    protected $signature = 'tenants:sync-nomes';

    protected $description = 'Sincroniza os nomes das instituições na base central';

    public function handle(): int
    {
        $failures = 0;

        foreach (Tenant::query()->orderBy('id')->cursor() as $tenant) {
            try {
                $nome = $tenant->run(fn (): ?string => Instituicao::query()->value('nome'));
            } catch (Throwable $exception) {
                $failures++;
                report($exception);
                $this->error("Falha ao obter o nome da instituição do tenant [{$tenant->getTenantKey()}]: {$exception->getMessage()}");

                continue;
            }

            $nome ??= PendingTenantData::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->value('nome');

            if ($nome !== null && $tenant->instituicao_nome !== $nome) {
                $tenant->update(['instituicao_nome' => $nome]);
            }
        }

        if ($failures > 0) {
            $this->error("Sincronização concluída com {$failures} falha(s).");

            return self::FAILURE;
        }

        $this->info('Nomes das instituições sincronizados com sucesso.');

        return self::SUCCESS;
    }
}
