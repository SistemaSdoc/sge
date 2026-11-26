<?php

namespace App\Models\Tenant;

use App\Models\Central\Disciplina;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'calendario_prova_id',
    'disciplina_id',
    'data',
    'hora_inicio',
    'hora_fim',
])]
class CalendarioProvaEntrada extends Model
{
    use HasUuid;

    protected $table = 'calendario_prova_entradas';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ============================================
    // RELAÇÕES
    // ============================================

    public function calendarioProva(): BelongsTo
    {
        return $this->belongsTo(CalendarioProva::class, 'calendario_prova_id');
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeDaData($query, string $data)
    {
        return $query->where('data', $data);
    }

    public function scopeDaDisciplina($query, string $disciplinaId)
    {
        return $query->where('disciplina_id', $disciplinaId);
    }
}
