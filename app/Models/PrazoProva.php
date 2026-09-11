<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PrazoProva extends Model
{
    use HasFactory, HasUuids;

    /**
     * A chave primária é do tipo UUID.
     */
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'prazos_provas';

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'instituicao_id',
        'disciplina_id',
        'classe_id',
        'criado_por',
        'tipo_prova',
        'titulo',
        'observacoes',
        'data_inicio',
        'data_limite',
        'ano_letivo',
        'periodo',
        'status',
        'permite_reenvio',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'data_inicio'  => 'datetime',
        'data_limite'  => 'datetime',
        'permite_reenvio' => 'boolean',
    ];

    /**
     * Atributos que serão incluídos nas respostas JSON.
     */
    protected $appends = [
        'status_label',
        'status_badge_class',
        'total_submissoes',
    ];

    // =====================================
    // RELACIONAMENTOS
    // =====================================

    public function instituicao()
{
    return $this->belongsTo(Instituicao::class);
}

    /**
     * Disciplina associada ao prazo (pode ser null para "todas").
     */
    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    /**
     * Classe associada ao prazo (pode ser null para "todas").
     */
    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    /**
     * Diretor que criou o prazo.
     */
    public function criador()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    /**
     * Submissões feitas para este prazo.
     */
    public function submissoes()
    {
        return $this->hasMany(SubmissaoProva::class, 'prazo_prova_id');
    }

    /**
     * Submissões atuais (excluindo versões substituídas).
     */
    public function submissoesAtuais()
    {
        return $this->hasMany(SubmissaoProva::class, 'prazo_prova_id')
                    ->where('estado', '!=', 'substituido');
    }

    // =====================================
    // SCOPES
    // =====================================

    /**
     * Scope para prazos abertos (status = aberto e dentro do período).
     */
    public function scopeAberto($query)
    {
        return $query->where('status', 'aberto')
                     ->where('data_inicio', '<=', now())
                     ->where('data_limite', '>=', now());
    }

    /**
     * Scope para prazos expirados (data_limite < agora).
     */
    public function scopeExpirado($query)
    {
        return $query->where('status', 'aberto')
                     ->where('data_limite', '<', now());
    }

    /**
     * Scope para prazos que estão fechados (manual ou automaticamente).
     */
    public function scopeFechado($query)
    {
        return $query->where('status', 'fechado');
    }

    // =====================================
    // MÉTODOS AUXILIARES
    // =====================================

    /**
     * Verifica se o prazo está expirado (data_limite passou).
     */
    public function isExpirado(): bool
    {
        return now()->gt($this->data_limite);
    }

    /**
     * Verifica se o prazo está aberto para submissões.
     */
    public function isAberto(): bool
    {
        return $this->status === 'aberto'
               && now()->between($this->data_inicio, $this->data_limite);
    }

    /**
     * Obtém a quantidade total de submissões (todas as versões).
     */
    public function getTotalSubmissoesAttribute(): int
    {
        return $this->submissoes()->count();
    }

    /**
     * Obtém a quantidade de submissões atuais (não substituídas).
     */
    public function getTotalSubmissoesAtuaisAttribute(): int
    {
        return $this->submissoesAtuais()->count();
    }

    /**
     * Verifica se todos os professores já submeteram (dentro das disciplinas/turmas abrangidas).
     */
    public function isTotalmenteSubmetido(): bool
    {
        // Lógica pode ser implementada posteriormente
        // Ex: contar quantos professores devem submeter e comparar com submissoesAtuais
        return false;
    }

    /**
     * Obtém o nome legível do status (dinâmico).
     */
    public function getStatusLabelAttribute(): string
    {
        // Se o status no banco for 'fechado', mantém (fechado manualmente)
        if ($this->status === 'fechado') {
            return 'Fechado';
        }

        // Se a data limite já passou, considera expirado (mesmo que status seja 'aberto')
        if ($this->isExpirado()) {
            return 'Expirado';
        }

        // Caso contrário, usa o valor do banco (aberto ou expirado)
        return match ($this->status) {
            'aberto'   => 'Aberto',
            'expirado' => 'Expirado',
            default    => ucfirst($this->status),
        };
    }

    /**
     * Classe CSS para o badge do status (dinâmico).
     */
public function getStatusBadgeClassAttribute(): string
{
    if ($this->status === 'fechado') {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800/40 dark:text-gray-300';
    }

    if ($this->isExpirado()) {
        return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
    }

    return match ($this->status) {
        'aberto'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'expirado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        default    => 'bg-gray-100 text-gray-800 dark:bg-gray-800/40 dark:text-gray-300',
    };
}

public function justificativas()
{
    return $this->hasMany(JustificativaNaoSubmissao::class, 'prazo_prova_id');
}

}