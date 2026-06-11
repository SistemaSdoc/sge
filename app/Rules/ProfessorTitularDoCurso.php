<?php

namespace App\Rules;

use App\Models\CursoTutelado;
use App\Models\CursoTuteladoProfessor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProfessorTitularDoCurso implements ValidationRule
{
    public function __construct(protected CursoTutelado $cursoTutelado) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $eTitular = CursoTuteladoProfessor::where('curso_tutelado_id', $this->cursoTutelado->id)
            ->where('professor_id', $value)
            ->where('tipo', 'principal')
            ->exists();

        if (! $eTitular) {
            $fail('O professor tutor deve ser titular do curso.');
        }
    }
}