<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nome',
    'slug',
    'descricao',
])]

class Permission extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id')
            ->using(RolePermission::class)
            ->withTimestamps();
    }
}
