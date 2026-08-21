<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnoLectivo extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ano_lectivos';

    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'activo',
        'estado',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (AnoLectivo $ano) {
            // Só gera o nome se não tiver sido definido
            if (empty($ano->nome)) {
                $anoInicio = $ano->data_inicio->year;
                $anoFim = $ano->data_fim->year;
                $ano->nome = "{$anoInicio}/{$anoFim}";
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

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function classeTurnoDisciplinas(): HasMany
    {
        return $this->hasMany(ClasseTurnoDisciplina::class, 'ano_lectivo_id');
    }

    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }
}
