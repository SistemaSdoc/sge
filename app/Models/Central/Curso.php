<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable([
    'nome',
    'descricao',
    'duracao_anos',
    'status',
])]
class Curso extends Model
{
    use CentralConnection, HasUuid, SoftDeletes;

    protected $table = 'cursos';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function getStatusTextoAttribute(): string
    {
        return $this->status === 1 ? 'Activo' : 'Inactivo';
    }
}
