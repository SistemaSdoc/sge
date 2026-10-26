<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'curso_tutelado_id',
    'user_id',
])]
class CursoTuteladoSecretario extends Pivot
{
    use HasUuid;

    protected $table = 'curso_tutelado_secretario';

    public $incrementing = false;

    protected $keyType = 'string';
}
