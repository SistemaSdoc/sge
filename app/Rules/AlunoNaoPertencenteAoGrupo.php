<?php

namespace App\Rules;

use App\Models\Aluno;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AlunoNaoPertencenteAoGrupo implements ValidationRule
{
    public function __construct(protected GrupoPap $grupoPap) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jaNoGrupoActual = ElementoGrupoPap::where('grupo_pap_id', $this->grupoPap->id)
            ->where('aluno_id', $value)
            ->exists();

        if ($jaNoGrupoActual) {
            $nome = Aluno::find($value)?->inscricao?->candidato?->nome ?? 'Aluno';
            $fail("$nome já pertence a este grupo.");

            return;
        }

        $jaEmOutroGrupo = ElementoGrupoPap::where('aluno_id', $value)
            ->whereIn('grupo_pap_id',
                GrupoPap::where('turma_id', $this->grupoPap->turma_id)
                    ->where('id', '!=', $this->grupoPap->id)
                    ->pluck('id')
            )
            ->exists();

        if ($jaEmOutroGrupo) {
            $nome = Aluno::find($value)?->inscricao?->candidato?->nome ?? 'Aluno';
            $fail("$nome já pertence a outro grupo PAP nesta turma.");
        }
    }
}
