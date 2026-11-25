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

    protected $description = 'Sincroniza os nomes e tipos das instituições na base central';

    public function handle(): int
    {
        $failures = 0;

        foreach (Tenant::query()->orderBy('id')->cursor() as $tenant) {
            try {
                $metadados = $tenant->run(fn (): array => [
                    'nome' => Instituicao::query()->value('nome'),
                    'tipo' => Instituicao::query()->value('tipo'),
                ]);
            } catch (Throwable $exception) {
                $failures++;
                report($exception);
                $this->error("Falha ao obter os metadados da instituição do tenant [{$tenant->getTenantKey()}]: {$exception->getMessage()}");

                continue;
            }

            if ($metadados['nome'] === null || $metadados['tipo'] === null) {
                $dadosPendentes = PendingTenantData::query()
                    ->where('tenant_id', $tenant->getTenantKey())
                    ->first(['nome', 'tipo']);

                $metadados['nome'] ??= $dadosPendentes?->nome;
                $metadados['tipo'] ??= $dadosPendentes?->tipo;
            }

            $atualizacoes = [];

            foreach ([
                'instituicao_nome' => $metadados['nome'],
                'instituicao_tipo' => $metadados['tipo'],
            ] as $campo => $valor) {
                if ($valor !== null && $tenant->{$campo} !== $valor) {
                    $atualizacoes[$campo] = $valor;
                }
            }

            if ($atualizacoes !== []) {
                $tenant->update($atualizacoes);
            }
        }

        if ($failures > 0) {
            $this->error("Sincronização concluída com {$failures} falha(s).");

            return self::FAILURE;
        }

        $this->info('Nomes e tipos das instituições sincronizados com sucesso.');

        return self::SUCCESS;
    }
}
