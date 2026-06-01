<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['user_id', 'role_id'])]
class RoleUser extends Pivot
{
    use HasUuid;

    protected $table = 'role_user';

    public $incrementing = false;

    public $keyType = 'string';
}
