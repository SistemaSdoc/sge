<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tipo',
    'subtipo',
    'aluno_id',
    'turma_id',
    'gerado_por_id',
])]
class DocumentoEmitido extends Model
{
    use HasUuid;

    protected $table = 'documentos_emitidos';

    protected $primaryKey = 'id';

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }
}