<?php

namespace App\Models\Tenant;

use App\Traits\HasSearch;
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
    use HasSearch, HasUuid;

    protected array $searchable = ['titulo', 'descricao', 'tipo', 'destinatario'];

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
