<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class CursoTuteladoShared extends Model
{
    use CentralConnection, HasUuids;

    protected $table = 'curso_tutelado_shared';

    protected $fillable = [
        'tenant_tutor_id',
        'tenant_tutelado_id',
        'curso_tutelado_tutelado_id',
        'tenant_tutor_nome',
        'curso_nome',
        'duracao_anos',
        'status',
    ];
}
