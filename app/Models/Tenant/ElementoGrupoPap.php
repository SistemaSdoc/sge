<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'grupo_pap_id',
    'aluno_id',
    'nota_individual',
])]

class ElementoGrupoPap extends Model
{
    use HasUuid;

    protected $table = 'elementos_grupo_pap';

    protected $primaryKey = 'id';

    public function grupoPap()
    {
        return $this->belongsTo(GrupoPap::class, 'grupo_pap_id');
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }
}
