<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubmissaoProva extends Model
{
    use HasFactory;
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'submissoes_provas';

    protected $fillable = [
        'prazo_prova_id',
        'professor_id',
        'disciplina_id',
        'classe_id',
        'turma_id',
        'caminho_prova',
        'caminho_chave',
        'versao',
        'comentario_professor',
        'estado',
        'parecer_diretor',
        'prazo_reenvio_ate',
        'data_submissao',

        // ── Compressão / Encriptação ──
        'tamanho_original_bytes',
        'tamanho_bytes',
        'comprimido',
        'encriptado',
        'algoritmo_encriptacao',
    ];

    protected $casts = [
        'data_submissao'         => 'datetime',
        'prazo_reenvio_ate'      => 'datetime',
        'versao'                 => 'integer',
        'tamanho_bytes'          => 'integer',
        'tamanho_original_bytes' => 'integer',
        'comprimido'             => 'boolean',
        'encriptado'             => 'boolean',
    ];

    // =====================================
    // RELACIONAMENTOS
    // =====================================

    public function prazo()
    {
        return $this->belongsTo(PrazoProva::class, 'prazo_prova_id');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    // =====================================
    // ATRIBUTOS VIRTUAIS — URLs
    // =====================================

    public function getUrlProvaAttribute(): string
    {
        return route('tenant.dashboard.professor.provas.arquivo', [
            'submissao' => $this->id,
            'tipo'      => 'prova',
        ]);
    }

    public function getUrlChaveAttribute(): string
    {
        return route('tenant.dashboard.professor.provas.arquivo', [
            'submissao' => $this->id,
            'tipo'      => 'chave',
        ]);
    }

    // =====================================
    // ATRIBUTOS VIRTUAIS — Tamanhos
    // =====================================

    public function getTamanhoFormatadoAttribute(): ?string
    {
        return $this->tamanho_bytes === null
            ? null
            : $this->humanSize($this->tamanho_bytes);
    }

    public function getTamanhoOriginalFormatadoAttribute(): ?string
    {
        return $this->tamanho_original_bytes === null
            ? null
            : $this->humanSize($this->tamanho_original_bytes);
    }

    public function getReducaoBytesAttribute(): ?int
    {
        if ($this->tamanho_original_bytes === null || $this->tamanho_bytes === null) {
            return null;
        }
        return $this->tamanho_original_bytes - $this->tamanho_bytes;
    }

    public function getReducaoPercentualAttribute(): ?float
    {
        if ($this->tamanho_original_bytes === null
            || $this->tamanho_original_bytes === 0
            || $this->tamanho_bytes === null) {
            return null;
        }
        return round((1 - $this->tamanho_bytes / $this->tamanho_original_bytes) * 100, 1);
    }

    // =====================================
    // ATRIBUTOS VIRTUAIS — Estado
    // =====================================

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendente'    => 'Pendente',
            'em_revisao'  => 'Em Revisão',
            'aprovado'    => 'Aprovado',
            'rejeitado'   => 'Rejeitado',
            'substituido' => 'Substituído',
            default       => ucfirst($this->estado ?? '—'),
        };
    }

    public function getEstadoBadgeClassAttribute(): string
    {
        return match ($this->estado) {
            'pendente'    => 'bg-warning text-dark',
            'em_revisao'  => 'bg-info text-dark',
            'aprovado'    => 'bg-success',
            'rejeitado'   => 'bg-danger',
            'substituido' => 'bg-secondary',
            default       => 'bg-light text-dark',
        };
    }

    // =====================================
    // HELPERS DE ESTADO
    // =====================================

    public function isPendente(): bool
    {
        return $this->estado === 'pendente';
    }

    public function isEditavel(): bool
    {
        return $this->prazo
            && $this->prazo->isAberto()
            && in_array($this->estado, ['pendente', 'em_revisao', 'rejeitado'], true);
    }

    public function isAprovado(): bool
    {
        return $this->estado === 'aprovado';
    }

    public function isRejeitado(): bool
    {
        return $this->estado === 'rejeitado';
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeAtuais($query)
    {
        return $query->where('estado', '!=', 'substituido');
    }

    public function scopePendentes($query)
    {
        return $query->where('estado', 'pendente');
    }

    public function scopeAprovadas($query)
    {
        return $query->where('estado', 'aprovado');
    }

    public function scopeRejeitadas($query)
    {
        return $query->where('estado', 'rejeitado');
    }

    // =====================================
    // AUXILIARES
    // =====================================

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1073741824, 2) . ' GB';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}