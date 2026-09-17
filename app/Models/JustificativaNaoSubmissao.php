<?php

namespace App\Models;

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
        'motivo',
        'status',
        'avaliado_por',
        'data_avaliacao',
        'parecer_diretor',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'data_justificativa' => 'datetime',
        'data_avaliacao' => 'datetime',
    ];

    /**
     * Accessor para exibir o status de forma legível.
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pendente' => 'Pendente',
            'aceita'   => 'Aceite',
            'recusada' => 'Recusada',
            default    => 'Desconhecido',
        };
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
     * Relacionamento com o usuário que avaliou (diretor).
     */
    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }
}