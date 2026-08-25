<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Events\CreatingDatabase;
use Stancl\Tenancy\Events\DatabaseCreated;

class CreateOrReuseTenantDatabase
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected TenantWithDatabase $tenant)
    {
    }

    public function handle(DatabaseManager $databaseManager): void
    {
        event(new CreatingDatabase($this->tenant));

        $this->tenant->database()->makeCredentials();

        $manager = $this->tenant->database()->manager();
        $dbName = $this->tenant->database()->getName();

        // Se o banco já existe, reutiliza — não tenta criar
        if ($manager->databaseExists($dbName)) {
            // Banco já existe — salta criação E seed
            $this->tenant->setInternal('seed_database', false);
            return;
        }

        $manager->createDatabase($this->tenant);

        event(new DatabaseCreated($this->tenant));
    }
}