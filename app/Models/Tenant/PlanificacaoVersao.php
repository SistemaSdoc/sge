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
            'versao'         => 'integer',
            'tamanho_bytes'  => 'integer',
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

    /**
     * Tamanho formatado (ex: "2.4 MB")
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';

        return round($bytes / 1073741824, 1) . ' GB';
    }

    /**
     * Extensão do ficheiro (pdf, doc, docx)
     */
    public function getExtensaoAttribute(): string
    {
        return pathinfo($this->nome_original, PATHINFO_EXTENSION);
    }

    /**
     * É PDF? (para preview no browser)
     */
    public function getEhPdfAttribute(): bool
    {
        return strtolower($this->extensao) === 'pdf';
    }
}