<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NivelEnsino extends Model
{
    use HasUuids;

    protected $table = 'niveis_ensino';

    protected $fillable = [
        'nome',
        'ordem',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cursoClasses()
    {
        return $this->hasMany(CursoClasse::class);
    }

    public function regrasAvaliacao()
    {
        return $this->hasMany(RegraAvaliacao::class);
    }
}
