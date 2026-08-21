<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RegraAvaliacao extends Model
{
    use HasUuids;

    protected $table = 'regras_avaliacao';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'instituicao_id',
        'ano_lectivo_id',
        'classe_id',
        'nivel_ensino_id',
        'nome',
        'media_minima_aprovacao',
        'frequencia_minima',
        'max_disciplinas_negativas',
        'permite_recurso',
        'activo',
    ];

    protected $casts = [
        'media_minima_aprovacao' => 'decimal:2',
        'frequencia_minima' => 'decimal:2',
        'permite_recurso' => 'boolean',
        'max_disciplinas_negativas' => 'integer',
        'activo' => 'boolean',
    ];

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function anoLectivo()
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function nivelEnsino()
    {
        return $this->belongsTo(NivelEnsino::class);
    }

    public static function regraAplicavel(
        string $instituicaoId,
        string $anoLectivoId,
        ?string $classeId = null,
        ?string $nivelEnsinoId = null,
    ): ?self {
        return self::where('instituicao_id', $instituicaoId)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->where('activo', true)
            ->where(function ($q) use ($classeId, $nivelEnsinoId) {
                // 1. Regra específica para a classe (prioridade 1)
                $q->where('classe_id', $classeId)
                    // 2. Regra para o nível de ensino (prioridade 2)
                    ->orWhere(function ($q2) use ($nivelEnsinoId) {
                        $q2->whereNull('classe_id')
                            ->where('nivel_ensino_id', $nivelEnsinoId);
                    })
                    // 3. Regra geral (prioridade 3)
                    ->orWhere(function ($q3) {
                        $q3->whereNull('classe_id')
                            ->whereNull('nivel_ensino_id');
                    });
            })
            ->orderByRaw('
                CASE
                    WHEN classe_id IS NOT NULL THEN 1
                    WHEN nivel_ensino_id IS NOT NULL THEN 2
                    ELSE 3
                END
            ')
            ->first();
    }
}
