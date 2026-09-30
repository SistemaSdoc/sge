<?php

namespace App\Models\Tenant;

use App\Models\Central\Curso as CursoCentral; // [ADICIONADO]
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'instituicao_id',
    'curso_id',
    'duracao_anos',
])]
class InstituicaoCurso extends Pivot
{
    use HasUuid;

    protected $table = 'instituicao_curso';

    public $incrementing = false;

    public $keyType = 'string';

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function curso(): BelongsTo
    {
        // [ALTERADO] o curso vive no catálogo central (CentralConnection), não na BD do tenant
        return $this->belongsTo(CursoCentral::class, 'curso_id')->withTrashed();
    }

    public function cursoTutelado()
    {
        return $this->hasOne(CursoTutelado::class, 'instituicao_curso_id');
    }

    public function aluno()
    {
        return $this->hasOne(Aluno::class, 'inscricao_id');
    }
}