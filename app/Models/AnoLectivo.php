<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnoLectivo extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ano_lectivos';

    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'activo',
        'status',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'activo' => 'boolean',
    ];

    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }

    /**
     * Retorna o ano lectivo actualmente activo.
     */
    public static function activo(): ?self
    {
        return static::where('activo', true)->first();
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class);
    }

    public function classeTurnoDisciplinas()
    {
        return $this->hasMany(ClasseTurnoDisciplina::class, 'ano_lectivo_id');
    }
}