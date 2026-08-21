<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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

    public function curso()
    {
        return $this->belongsTo(Curso::class);
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
