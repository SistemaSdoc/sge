<?php

use App\Listeners\ActualizarProgressaoTenant;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Events\DatabaseMigrated;

it('stores migration progress in the untagged database cache store', function () {
    $tenant = Tenant::make(['id' => 'progress-test-tenant']);
    $key = "progresso_tenant_{$tenant->id}";

    Cache::store('database')->forget($key);

    (new ActualizarProgressaoTenant)->handle(new DatabaseMigrated($tenant));

    expect(Cache::store('database')->get($key))->toMatchArray([
        'etapa' => 'migrations_feitas',
        'percentagem' => 50,
        'status' => 'em_progresso',
    ]);

    Cache::store('database')->forget($key);
});
