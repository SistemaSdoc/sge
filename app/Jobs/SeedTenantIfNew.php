<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class SeedTenantIfNew
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected TenantWithDatabase $tenant) {}

    public function handle(): void
    {
        // Banco já existia — salta seed
        if ($this->tenant->getInternal('seed_database') === false) {
            return;
        }

        // Banco novo mas já tem dados — salta seed
        $hasData = $this->tenant->run(
            fn () => \DB::table('classes')->exists()
        );

        if ($hasData) {
            return;
        }

        Artisan::call('tenants:seed', [
            '--tenants' => [$this->tenant->getTenantKey()],
        ]);
    }
}