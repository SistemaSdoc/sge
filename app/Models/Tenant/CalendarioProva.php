<?php

namespace App\Models\Tenant;

use App\Models\Central\AnoLectivo;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ano_lectivo_id',
    'curso_classe_turno_id',
    'tipo',
    'trimestre',
    'ativo',
])]
class CalendarioProva extends Model
{
    use HasUuid;

    protected $table = 'calendario_provas';

    protected $primaryKey = 'id';

    const TIPO_TRIMESTRAL = 'trimestral';

    const TIPO_RECURSO = 'recurso';

    const TIPO_EXAME_ESPECIAL = 'exame_especial';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'trimestre' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ============================================
    // RELAÇÕES
    // ============================================

    public function entradas(): HasMany
    {
        return $this->hasMany(CalendarioProvaEntrada::class, 'calendario_prova_id');
    }

    public function cursoClasseTurno(): BelongsTo
    {
        return $this->belongsTo(CursoClasseTurno::class, 'curso_classe_turno_id');
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDoAnoLectivo($query, string $anoLectivoId)
    {
        return $query->where('ano_lectivo_id', $anoLectivoId);
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDoTrimestre($query, int $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }

    public static function trimestreForTipo(string $tipo, int|string|null $trimestre): ?int
    {
        return $tipo === self::TIPO_TRIMESTRAL && $trimestre !== null
            ? (int) $trimestre
            : null;
    }

    // ============================================
    // HELPERS
    // ============================================

    public function getTituloAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_TRIMESTRAL => "Calendário do {$this->trimestre}º Trimestre",
            self::TIPO_RECURSO => 'Calendário de Recurso',
            self::TIPO_EXAME_ESPECIAL => 'Calendário de Exame Especial',
            default => 'Calendário de Provas',
        };
    }
}
