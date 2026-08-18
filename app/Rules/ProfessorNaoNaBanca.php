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
    public function __construct(
        protected GrupoPap $grupoPap,
        protected ?BancaJuriPap $bancaJuriPap = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = BancaJuriPap::where('grupo_pap_id', $this->grupoPap->id)
            ->where('professor_id', $value);

        // No update, excluir o próprio registo da verificação
        if ($this->bancaJuriPap) {
            $query->where('id', '!=', $this->bancaJuriPap->id);
        }

        if ($query->exists()) {
            $nome = Professor::with('user:id,nome')
                ->find($value)?->user?->nome ?? 'Professor';
            $fail("$nome já pertence à banca deste grupo.");
        }
    }
}
