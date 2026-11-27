<?php

namespace App\Actions\Tenant\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DeleteRole
{
    public function handle(Role $role): void
    {
        DB::transaction(fn (): ?bool => $role->delete());
    }
}
