<?php

namespace App\Models\Tenant;

use App\Models\Central\AnoLectivo;
use App\Models\Central\Disciplina;
use App\Models\Tenant\CursoTutelado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'instituicao_id',
    'ano_letivo_id',
    'disciplina_id',
    'curso_id',
    'classe_id',
    'periodo',
    'titulo',
    'descricao',
    'versao_atual',
    'created_by',
])]
class Planificacao extends Model
{
    use HasUuid;

    protected $table = 'planificacoes';

    public $incrementing = false;
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'versao_atual' => 'integer',
        ];
    }

    // ============================================================
    // RELAÇÕES
    // ============================================================

    public function versoes(): HasMany
    {
        return $this->hasMany(PlanificacaoVersao::class, 'planificacao_id')
            ->orderByDesc('versao');
    }

    public function versaoAtual(): BelongsTo
    {
        // Helper: versão mais recente (não é relação direta)
        // Usar ->versaoAtual()->first() após with()
        return $this->belongsTo(PlanificacaoVersao::class, 'id', 'planificacao_id')
            ->whereColumn('versao', 'planificacoes.versao_atual');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_letivo_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function curso(): BelongsTo
{
    return $this->belongsTo(CursoTutelado::class, 'curso_id');
}
}