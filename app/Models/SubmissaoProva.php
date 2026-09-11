<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class SubmissaoProva extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    use HasUuid; // <-- deve estar aqui

    public $incrementing = false;
    protected $keyType = 'string';


    protected $table = 'submissoes_provas';

    protected $fillable = [
        'prazo_prova_id',
        'professor_id',
        'disciplina_id',
        'classe_id',
        'caminho_prova',
        'caminho_chave',
        'versao',
        'comentario_professor',
        'estado',
        'turma_id',
        'parecer_diretor',
        'data_submissao',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'data_submissao' => 'datetime',
        'versao' => 'integer',
    ];

    // =====================================
    // RELACIONAMENTOS
    // =====================================

    /**
     * Prazo a que esta submissão pertence.
     */
    public function prazo()
    {
        return $this->belongsTo(PrazoProva::class, 'prazo_prova_id');
    }

    /**
     * Professor que fez a submissão.
     */
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    /**
     * Disciplina da prova.
     */
    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }


public function turma()
{
    return $this->belongsTo(Turma::class, 'turma_id');
}

    /**
     * Classe (turma) da prova.
     */
    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    // =====================================
    // ATRIBUTOS VIRTUAIS (Accessors)
    // =====================================

    /**
     * Obtém a URL pública do arquivo da prova.
     */
    public function getUrlProvaAttribute(): string
    {
        return asset('storage/' . $this->caminho_prova);
    }

    /**
     * Obtém a URL pública do arquivo da chave/gabarito.
     */
    public function getUrlChaveAttribute(): string
    {
        return asset('storage/' . $this->caminho_chave);
    }

    /**
     * Obtém o nome legível do estado da submissão.
     */
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendente'    => 'Pendente',
            'em_revisao'  => 'Em Revisão',
            'aprovado'    => 'Aprovado',
            'rejeitado'   => 'Rejeitado',
            'substituido' => 'Substituído',
            default       => ucfirst($this->estado),
        };
    }

    /**
     * Obtém a classe CSS para o badge do estado.
     */
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

    /**
     * Verifica se a submissão está pendente de avaliação.
     */
    public function isPendente(): bool
    {
        return $this->estado === 'pendente';
    }

    /**
     * Verifica se a submissão pode ser editada (re-submetida).
     * Condições: prazo aberto, estado permitido.
     */
    public function isEditavel(): bool
    {
        return $this->prazo && $this->prazo->isAberto()
               && in_array($this->estado, ['pendente', 'em_revisao', 'rejeitado']);
    }

    /**
     * Verifica se a submissão está aprovada.
     */
    public function isAprovado(): bool
    {
        return $this->estado === 'aprovado';
    }

    /**
     * Verifica se a submissão foi rejeitada.
     */
    public function isRejeitado(): bool
    {
        return $this->estado === 'rejeitado';
    }

    // =====================================
    // SCOPES
    // =====================================

    /**
     * Scope para submissões atuais (exclui versões substituídas).
     */
    public function scopeAtuais($query)
    {
        return $query->where('estado', '!=', 'substituido');
    }

    /**
     * Scope para submissões pendentes.
     */
    public function scopePendentes($query)
    {
        return $query->where('estado', 'pendente');
    }

    /**
     * Scope para submissões aprovadas.
     */
    public function scopeAprovadas($query)
    {
        return $query->where('estado', 'aprovado');
    }

    /**
     * Scope para submissões rejeitadas.
     */
    public function scopeRejeitadas($query)
    {
        return $query->where('estado', 'rejeitado');
    }
    protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->id)) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        }
    });
}

}