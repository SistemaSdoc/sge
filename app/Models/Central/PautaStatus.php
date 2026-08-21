<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PautaStatus extends Model
{
    use HasUuid;

    protected $table = 'pauta_status';

    protected $fillable = [
        'turma_disciplina_professor_id',
        'periodo',
        'status',
        'finalizada_automaticamente',
        'finalizada_em',
    ];

    protected $casts = ['finalizada_em' => 'datetime'];

    public function estaFinalizada(): bool
    {
        return $this->status === 'finalizada';
    }

    public function turmaDisciplinaProfessor()
    {
        return $this->belongsTo(TurmaDisciplinaProfessor::class, 'turma_disciplina_professor_id');
    }
}
