<?php

namespace App\Rules;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlunoNaoPertencenteAoGrupo implements ValidationRule
{
    public function __construct(
        protected GrupoPap $grupoPap,
        protected bool $isUpdate = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // No update, ignorar alunos que já estão neste grupo
        if (! $this->isUpdate) {
            $jaNoGrupoActual = ElementoGrupoPap::where('grupo_pap_id', $this->grupoPap->id)
                ->where('aluno_id', $value)
                ->exists();

            if ($jaNoGrupoActual) {
                $nome = Aluno::find($value)?->inscricao?->candidato?->nome ?? 'Aluno';
                $fail("$nome já pertence a este grupo.");

                return;
            }
        }

        $jaEmOutroGrupo = ElementoGrupoPap::where('aluno_id', $value)
            ->whereIn(
                'grupo_pap_id',
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
