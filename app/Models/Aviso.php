<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'titulo',
    'descricao',
    'tipo',
    'data',
    'ativo',
    'instituicao_id',
    'destinatario',
])]
class Aviso extends Model
{
    use HasUuid;

    protected $table = 'avisos';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'data' => 'datetime',
            'ativo' => 'boolean',
        ];
    }
}
