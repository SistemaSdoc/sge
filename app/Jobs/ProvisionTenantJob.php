<?php

namespace App\Jobs;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Jobs\SeedDatabase;
use Throwable;

class ProvisionTenantJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 3600;

    public function __construct(public Tenant $tenant) {}

    public function handle(DatabaseManager $databaseManager): void
    {
        $tenantDatabaseManager = $this->tenant->database()->manager();

        if (! $tenantDatabaseManager->databaseExists($this->tenant->database()->getName())) {
            $databaseCreated = app()->call([new CreateDatabase($this->tenant), 'handle'], [
                'databaseManager' => $databaseManager,
            ]);

            if ($databaseCreated === false) {
                throw new \RuntimeException('A criação da base de dados foi interrompida.');
            }
        }

        app()->call([new MigrateDatabase($this->tenant), 'handle']);
        app()->call([new SeedDatabase($this->tenant), 'handle']);
        app()->call([new CreateTenantInstitution($this->tenant), 'handle']);

        Tenant::query()->whereKey($this->tenant->getTenantKey())->update([
            'status' => $this->tenant->provisioning_target_status ?? TenantStatus::ACTIVE,
            'provisioning_error' => null,
            'provisioning_finished_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Tenant::query()->whereKey($this->tenant->getTenantKey())->update([
            'status' => TenantStatus::FAILED,
            'provisioning_error' => $exception?->getMessage() ?? 'Falha desconhecida durante a configuração.',
            'provisioning_finished_at' => now(),
        ]);

        Log::error('Tenant provisioning failed.', [
            'tenant_id' => $this->tenant->getTenantKey(),
            'exception' => $exception,
        ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->tenant->getTenantKey();
    }
}
