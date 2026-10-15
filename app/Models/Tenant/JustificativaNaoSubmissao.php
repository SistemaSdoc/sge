<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class JustificativaNaoSubmissao extends Model
{
    use HasUuids;

    /**
     * Nome da tabela associada (já definido na migration).
     */
    protected $table = 'justificativas_nao_submissao';

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'prazo_prova_id',
        'professor_id',
        'turma_id',
        'motivo',
        'status',
        'avaliado_por',
        'data_avaliacao',
        'parecer_diretor',
        'prazo_prorrogado_ate',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'data_justificativa' => 'datetime',
        'data_avaliacao' => 'datetime',
        'prazo_prorrogado_ate' => 'datetime',
    ];

    /**
     * Accessor para exibir o status de forma legível.
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pendente' => 'Pendente',
            'aceita' => 'Aceite',
            'recusada' => 'Recusada',
            default => 'Desconhecido',
        };
    }


    /**
     * Verifica se a justificativa está aceite e o prazo extra ainda está válido.
     */
    public function temPrazoExtraValido(): bool
    {
        if ($this->status !== 'aceita') {
            return false;
        }

        // Sem prazo definido = sem extensão (não pode submeter)
        if (! $this->prazo_prorrogado_ate) {
            return false;
        }

        return $this->prazo_prorrogado_ate->isFuture();
    }

    /**
     * Dias restantes do prazo extra (arredondado para cima).
     */
    public function diasRestantes(): ?int
    {
        if (! $this->temPrazoExtraValido()) {
            return null;
        }

        return (int) ceil(now()->diffInDays($this->prazo_prorrogado_ate, false));
    }
    /**
     * Relacionamento com o prazo da prova.
     */
    public function prazo()
    {
        return $this->belongsTo(PrazoProva::class, 'prazo_prova_id');
    }

    /**
     * Relacionamento com o professor.
     */
    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }


    /**
     * Relacionamento com a turma.
     */

    public function turma()
    {
        return $this->belongsTo(\App\Models\Tenant\Turma::class);
    }

    /**
     * Relacionamento com o usuário que avaliou (diretor).
     */
    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por')->withTrashed();
    }
}