<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class RegraAvaliacao extends Model
{
    use HasUuids;

    protected $table = 'regras_avaliacao';

    protected $fillable = [
        'instituicao_id',
        'ano_lectivo_id',
        'nivel_ensino',
        'classe_id',
        'formula_calculo',
        'pesos',
        'media_minima_aprovacao',
        'media_minima_recurso',
        'max_disciplinas_recurso',
        'permite_recurso',
        'nota_minima_recurso',
        'formula_recurso',
        'considerar_faltas',
        'frequencia_minima',
        'excluir_por_faltas',
    ];

    protected $casts = [
        'pesos' => 'array',
        'permite_recurso' => 'boolean',
        'considerar_faltas' => 'boolean',
        'excluir_por_faltas' => 'boolean',
        'media_minima_aprovacao' => 'float',
        'media_minima_recurso' => 'float',
        'nota_minima_recurso' => 'float',
        'max_disciplinas_recurso' => 'integer',
        'frequencia_minima' => 'integer',
    ];

    // ─────────────────────────────────────────────
    // RELAÇÕES
    // ─────────────────────────────────────────────

    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function disciplinasEliminatorias(): HasMany
    {
        return $this->hasMany(RegraDisciplinaEliminatoria::class, 'regra_avaliacao_id');
    }
    
    /**
     * Resolve a regra correcta para um TurmaAluno.
     * Prioridade: classe específica → nível de ensino → instituição geral → default.
     */
    public static function resolverPara(TurmaAluno $turmaAluno): self
    {
        $turmaAluno->loadMissing([
            'turma.anoLectivo',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicao',
        ]);

        $classe = $turmaAluno->turma->cursoClasseTurno->cursoClasse->classe;
        $anoLectivoId = $turmaAluno->turma->ano_lectivo_id;
        $instituicaoId = $turmaAluno->turma
            ->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado
            ->instituicao_id;

        $cacheKey = "regra_avaliacao.{$instituicaoId}.{$anoLectivoId}.{$classe->id}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use (
            $classe, $anoLectivoId, $instituicaoId
        ) {
            $regra = self::with('disciplinasEliminatorias')
                ->where('instituicao_id', $instituicaoId)
                ->where('ano_lectivo_id', $anoLectivoId)
                ->where(function ($q) use ($classe) {
                    $q->where('classe_id', $classe->id)                         // 1. classe específica
                        ->orWhere(function ($q2) use ($classe) {
                            $q2->whereNull('classe_id')
                                ->where('nivel_ensino', $classe->nivel_ensino);     // 2. nível de ensino
                        })
                        ->orWhere(function ($q3) {
                            $q3->whereNull('classe_id')->whereNull('nivel_ensino'); // 3. regra geral
                        });
                })
                ->orderByRaw('
                    CASE
                        WHEN classe_id IS NOT NULL THEN 1
                        WHEN nivel_ensino IS NOT NULL THEN 2
                        ELSE 3
                    END
                ')
                ->first();

            return $regra ?? self::default();
        });
    }

    // ─────────────────────────────────────────────
    // REGRA PADRÃO (fallback quando nada configurado)
    // ─────────────────────────────────────────────

    public static function default(): self
    {
        return new self([
            'formula_calculo' => 'simples',
            'media_minima_aprovacao' => 10,
            'media_minima_recurso' => 8,
            'max_disciplinas_recurso' => 3,
            'permite_recurso' => true,
            'nota_minima_recurso' => 10,
            'formula_recurso' => 'so_exame',
            'considerar_faltas' => true,
            'frequencia_minima' => 75,
            'excluir_por_faltas' => true,
        ]);
    }
}
