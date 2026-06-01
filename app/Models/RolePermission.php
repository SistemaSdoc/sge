<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'role_id',
    'permission_id',
])]

class RolePermission extends Pivot
{
    use HasUuid;

    protected $table = 'role_permission';

    public $incrementing = false;

    public $keyType = 'string';
}
