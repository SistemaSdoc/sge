<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SolicitacaoEdicaoPauta extends Model
{
    use HasUuid;

    protected $table = 'solicitacoes_edicao_pauta';

    protected $fillable = [
        'turma_disciplina_professor_id',
        'periodo',
        'professor_user_id',
        'motivo',
        'status',
        'decidido_por',
        'decidido_em',
        'observacao',
        'usada_em',
    ];

    protected $casts = [
        'decidido_em' => 'datetime',
        'usada_em' => 'datetime',
    ];
}
