<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class AnoLectivo extends Model
{
    use CentralConnection, HasUuids, SoftDeletes;

    protected $table = 'ano_lectivos';

    protected $fillable = [
        'ano_inicio',
        'nome',
        'data_inicio',
        'data_fim',
        'activo',
        'estado',
    ];

    protected $casts = [
        'ano_inicio' => 'integer',
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $ano): void {
            if ($ano->ano_inicio === null && $ano->data_inicio !== null) {
                $ano->ano_inicio = $ano->data_inicio->year;
            }

            if ($ano->ano_inicio !== null) {
                $ano->nome = "{$ano->ano_inicio}/".($ano->ano_inicio + 1);
            }
        });
    }

    public function scopeAtivo($query)
    {
        return $query->where('activo', true);
    }

    public static function activo(): ?self
    {
        return static::ativo()->first();
    }
}
