<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'planificacao_id',
    'versao',
    'caminho_ficheiro',
    'nome_original',
    'tamanho_bytes',
    'mime_type',
    'encriptado',
    'comprimido',
    'tamanho_original_bytes',
    'algoritmo_encriptacao',
    'uploaded_by',
])]
class PlanificacaoVersao extends Model
{
    use HasUuid;

    protected $table = 'planificacao_versoes';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'versao'                 => 'integer',
            'tamanho_bytes'          => 'integer',
            'tamanho_original_bytes' => 'integer',
            'encriptado'             => 'boolean',
            'comprimido'             => 'boolean',
        ];
    }

    public function planificacao(): BelongsTo
    {
        return $this->belongsTo(Planificacao::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Tamanho ────────────────────────────────────────────

    public function getTamanhoFormatadoAttribute(): string
    {
        return $this->humanSize($this->tamanho_bytes);
    }

    public function getTamanhoOriginalFormatadoAttribute(): ?string
    {
        return $this->tamanho_original_bytes === null
            ? null
            : $this->humanSize($this->tamanho_original_bytes);
    }

    public function getReducaoBytesAttribute(): ?int
    {
        return $this->tamanho_original_bytes === null
            ? null
            : $this->tamanho_original_bytes - $this->tamanho_bytes;
    }

    public function getReducaoPercentualAttribute(): ?float
    {
        if ($this->tamanho_original_bytes === null || $this->tamanho_original_bytes === 0) {
            return null;
        }

        return round((1 - $this->tamanho_bytes / $this->tamanho_original_bytes) * 100, 1);
    }

    // ── Extensão / tipo ───────────────────────────────────

    public function getExtensaoAttribute(): string
    {
        return strtolower(pathinfo($this->nome_original, PATHINFO_EXTENSION));
    }

    public function getEhPdfAttribute(): bool
    {
        return $this->extensao === 'pdf';
    }

    // ── Helper ─────────────────────────────────────────────

    private function humanSize(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 1) . ' GB';
    }
}