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
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }

    protected static function booted(): void
    {
        static::saving(function (AnoLectivo $ano) {
            $ano->nome = $ano->data_inicio->format('Y').'/'.$ano->data_fim->format('y');
        });
    }

    public function getEstadoAttribute(): string
    {
        $hoje = now()->startOfDay();

        return match (true) {
            $hoje->lt($this->data_inicio) => 'planeado',
            $hoje->gt($this->data_fim) => 'encerrado',
            default => 'a_decorrer',
        };
    }

    public static function activo(): ?self
    {
        return static::whereDate('data_inicio', '<=', now())
            ->whereDate('data_fim', '>=', now())
            ->first();
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
