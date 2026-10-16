<?php

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Events\TenantDeleted;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Tests\TestCase;

uses(TestCase::class);

test('tenant database deletion runs only after the central transaction commits', function () {
    Bus::fake([DeleteDatabase::class]);

    DB::beginTransaction();
    event(new TenantDeleted(new Tenant(['id' => 'tenant-test'])));

    Bus::assertNothingDispatched();

    DB::commit();

    Bus::assertDispatchedSync(DeleteDatabase::class);
});
