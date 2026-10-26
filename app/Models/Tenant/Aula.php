<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aula extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'aulas';

    protected $fillable = [
        'classe_turno_disciplina_horario_id',
        'turma_id',
        'disciplina_id',
        'professor_id',
        'professor_original_id',
        'data',
        'hora_inicio',
        'hora_fim',
        'tipo',
        'titulo',
        'conteudo_previsto',
        'sumario',
        'tpc',
        'observacoes',
        'estado',
        'publicado_em',
    ];

    protected $casts = [
        'data'         => 'date',
        'publicado_em' => 'datetime',
    ];

    // ── Relations ───────────────────────────────────────────

    public function horario(): BelongsTo
    {
        return $this->belongsTo(
            ClasseTurnoDisciplinaHorario::class,
            'classe_turno_disciplina_horario_id'
        );
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function professorOriginal(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'professor_original_id');
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopePublicado($q)
    {
        return $q->where('estado', 'publicado');
    }

    public function scopeRascunho($q)
    {
        return $q->where('estado', 'rascunho');
    }

    public function scopeDaTurma($q, string $turmaId)
    {
        return $q->where('turma_id', $turmaId);
    }

    public function scopeDoProfessor($q, string $professorId)
    {
        return $q->where(function ($sub) use ($professorId) {
            $sub->where('professor_id', $professorId)
                ->orWhere('professor_original_id', $professorId);
        });
    }

    public function scopeEntreDatas($q, $de, $ate)
    {
        return $q->whereBetween('data', [$de, $ate]);
    }

    // ── Accessors ───────────────────────────────────────────

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'rascunho'  => 'Rascunho',
            'publicado' => 'Publicado',
            'cancelado' => 'Cancelado',
            default     => ucfirst($this->estado ?? '—'),
        };
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'normal'       => 'Normal',
            'substituicao' => 'Substituição',
            'extra'        => 'Extra',
            'reposicao'    => 'Reposição',
            default        => ucfirst($this->tipo ?? '—'),
        };
    }

    public function getEstaEditavelAttribute(): bool
    {
        if ($this->estado === 'cancelado') {
            return false;
        }

        if ($this->publicado_em === null) {
            return true;
        }

        $horas = (int) config('aulas.janela_edicao_horas', 48);

        return $this->publicado_em->diffInHours(now()) < $horas;
    }

    public function getHorasRestantesEdicaoAttribute(): ?int
    {
        if (! $this->publicado_em || ! $this->esta_editavel) {
            return null;
        }

        $horas      = (int) config('aulas.janela_edicao_horas', 48);
        $decorridas = $this->publicado_em->diffInHours(now());

        return max(0, $horas - $decorridas);
    }

    public function getTemSumarioAttribute(): bool
    {
        return ! empty(trim((string) $this->sumario));
    }
}