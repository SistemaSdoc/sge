<?php

use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Permission;

it('keeps central permissions technical and without friendly labels', function () {
    Permission::query()->where('guard_name', 'web')->delete();

    app(PermissionSeeder::class)->run();

    $permission = Permission::where('guard_name', 'web')->where('name', 'tenants.viewAny')->first();

    expect($permission)->not->toBeNull()
        ->and($permission->label)->toBeNull();
});
