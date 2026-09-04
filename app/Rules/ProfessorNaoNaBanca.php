<?php

namespace App\Rules;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Professor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProfessorNaoNaBanca implements ValidationRule
{
    public function __construct(
        GrupoPap|string $grupoPap,
        BancaJuriPap|string|null $bancaJuriPap = null,
    ) {
        $this->grupoPapId = $grupoPap instanceof GrupoPap
            ? (string) $grupoPap->getKey()
            : $grupoPap;
        $this->bancaJuriPapId = $bancaJuriPap instanceof BancaJuriPap
            ? (string) $bancaJuriPap->getKey()
            : $bancaJuriPap;
    }

    protected string $grupoPapId;

    protected ?string $bancaJuriPapId;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = BancaJuriPap::where('grupo_pap_id', $this->grupoPapId)
            ->where('professor_id', $value);

        // No update, excluir o próprio registo da verificação
        if ($this->bancaJuriPapId !== null) {
            $query->where('id', '!=', $this->bancaJuriPapId);
        }

        if ($query->exists()) {
            $nome = Professor::with('user:id,nome')
                ->find($value)?->user?->nome ?? 'Professor';
            $fail("$nome já pertence à banca deste grupo.");
        }
    }
}
