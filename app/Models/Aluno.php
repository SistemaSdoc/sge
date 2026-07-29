<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'inscricao_id',
    'matricula',
    'situacao',
])]

class Aluno extends Model
{
    use HasUuid;

    protected $table = 'alunos';

    protected $primaryKey = 'id';

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('situacao', 'activo');
    }

    public function scopeFinalistas($query)
    {
        return $query->where('situacao', 'finalista');
    }

    public function scopeConcluidos($query)
    {
        return $query->where('situacao', 'concluido');
    }

    public function scopeDoAnoLectivo($query, $anoLectivoId)
    {
        return $query->where(function ($q) use ($anoLectivoId) {
            $q->whereHas('turmas', function ($q2) use ($anoLectivoId) {
                $q2->where('turmas.ano_lectivo_id', $anoLectivoId);
            })
            ->orWhere(function ($q2) use ($anoLectivoId) {
                $q2->whereDoesntHave('turmas')
                    ->whereHas('inscricao', function ($q3) use ($anoLectivoId) {
                        $q3->where('ano_lectivo_id', $anoLectivoId);
                    });
            });
        });
    }

    public function scopeDoAnoLectivoActivo($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('turmas', function ($q2) {
                $q2->whereHas('anoLectivo', fn($q3) => $q3->ativo());
            })
            ->orWhere(function ($q2) {
                $q2->whereDoesntHave('turmas')
                    ->whereHas('inscricao.anoLectivo', fn($q3) => $q3->ativo());
            });
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->using(TurmaAluno::class)
            ->withTimestamps();
    }

    public function turmaActual()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot('activo', 'situacao')
            ->wherePivotIn('situacao', ['activo', 'pap_concluido']);
    }

    public function turmaAlunoActual(): ?TurmaAluno
    {
        $turma = $this->turmaActual()->first();

        if (!$turma) {
            return null;
        }

        return TurmaAluno::where('aluno_id', $this->id)
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->first();
    }

    public function historicoTurmas()
    {
        return $this->turmas()
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->orderByPivot('ano_lectivo', 'asc');
    }

    public function anoLectivoAtual(): ?AnoLectivo
    {
        $turma = $this->turmaActual()->first();

        return $turma ? AnoLectivo::find($turma->pivot->ano_lectivo_id) : null;
    }

    // ============================================
    // RELACIONAMENTO COM PROPINAS
    // ============================================
    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }

    // ============================================
    // MÉTODOS DE VERIFICAÇÃO DE DÉBITOS
    // ============================================

    /**
     * Verifica se o aluno tem débitos pendentes (propinas em atraso)
     * 
     * @return bool True se tiver débitos, False se estiver em dia
     */
    public function temDebitosPendentes(): bool
    {
        // Verifica se existe propina com estado 'atrasado'
        return $this->propinas()
            ->where('estado', 'atrasado')  // ← ATENÇÃO: usa 'estado', não 'status'
            ->exists();
    }

    /**
     * Verifica se o aluno está em dia com as propinas
     * 
     * @return bool True se estiver em dia, False se tiver débitos
     */
    public function estaEmDia(): bool
    {
        return !$this->temDebitosPendentes();
    }

    public function grupoPap()
    {
        return $this->hasOneThrough(
            GrupoPap::class,
            ElementoGrupoPap::class,
            'aluno_id',
            'id',
            'id',
            'grupo_pap_id'
        );
    }
}