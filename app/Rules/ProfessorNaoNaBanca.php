<?php

namespace App\Rules;

use App\Models\BancaJuriPap;
use App\Models\GrupoPap;
use App\Models\Professor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProfessorNaoNaBanca implements ValidationRule
{
    public function __construct(protected GrupoPap $grupoPap) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jaExiste = BancaJuriPap::where('grupo_pap_id', $this->grupoPap->id)
            ->where('professor_id', $value)
            ->exists();

        if ($jaExiste) {
            $nome = Professor::with('user:id,nome')
                ->find($value)?->user?->nome ?? 'Professor';
            $fail("$nome já pertence à banca deste grupo.");
        }
    }
}
