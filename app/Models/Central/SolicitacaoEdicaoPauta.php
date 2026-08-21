<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SolicitacaoEdicaoPauta extends Model
{
    use HasUuid;

    protected $table = 'solicitacoes_edicao_pauta';

    protected $fillable = [
        'turma_disciplina_professor_id',
        'periodo',
        'tipo',
        'professor_user_id',
        'motivo',
        'status',
        'decidido_por',
        'decidido_em',
        'prazo_edicao_ate',
        'observacao',
        'usada_em',
    ];

    protected $casts = [
        'decidido_em' => 'datetime',
        'prazo_edicao_ate' => 'datetime',
        'usada_em' => 'datetime',
    ];

    public function turmaDisciplinaProfessor()
    {
        return $this->belongsTo(TurmaDisciplinaProfessor::class, 'turma_disciplina_professor_id');
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_user_id');
    }

    public function decididoPor()
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}
