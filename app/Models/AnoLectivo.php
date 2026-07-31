<?php

namespace App\Models;

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
            $ano->nome = $ano->data_inicio->format('Y').'/'.$ano->data_fim->format('y');
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
