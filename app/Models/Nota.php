<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['turma_aluno_id', 'turma_disciplina_professor_id', 'periodo', 'mac', 'nota_prova_professor', 'nota_prova_trimestral', 'media_trimestral', 'media_final', 'faltas', 'situacao_trimestral', 'situacao_anual', 'observacao'])]
class Nota extends Model
{
    use HasUuid;

    public const FALTAS_EEF_TRIMESTRAL = 8;

    public const NOTA_MINIMA_APTO = 10.0;

    public const NOTA_RECUPERACAO_MIN = 7.0;

    public const NOTA_RECUPERACAO_MAX = 9.5;

    public const NOTA_MAX = 20.0;

    public const NOTA_MIN = 0.0;

    protected function casts(): array
    {
        return [
            'periodo' => 'integer',
        ];
    }

    public function turmaAluno()
    {
        return $this->belongsTo(TurmaAluno::class);
    }

    public function turmaDisciplinaProfessor()
    {
        return $this->belongsTo(TurmaDisciplinaProfessor::class);
    }

    protected function mac(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v !== null ? (float) $v : null,
            set: fn ($v) => $this->sanitizarNota($v),
        );
    }

    protected function notaProvaProfessor(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v !== null ? (float) $v : null,
            set: fn ($v) => $this->sanitizarNota($v),
        );
    }

    protected function notaProvaTrimestral(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v !== null ? (float) $v : null,
            set: fn ($v) => $this->sanitizarNota($v),
        );
    }

    protected function mediaTrimestral(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v !== null ? (float) $v : null,
            set: fn ($v) => $this->sanitizarMedia($v),
        );
    }

    protected function mediaFinal(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v !== null ? (float) $v : null,
            set: fn ($v) => $this->sanitizarMedia($v),
        );
    }

    protected function faltas(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => max(0, (int) ($v ?? 0)),
            set: fn ($v) => max(0, (int) ($v ?? 0)),
        );
    }
}
