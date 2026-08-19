<?php

namespace App\Models\tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'professor_id',
    'grupo_pap_id',
    'funcao',
])]
class BancaJuriPap extends Model
{
    use HasUuid;

    protected $table = 'banca_juri_pap';

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function grupoPap()
    {
        return $this->belongsTo(GrupoPap::class, 'grupo_pap_id');
    }
}
