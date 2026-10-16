<?php

namespace App\Rules;

use App\Services\Tenant\TemaPapUnicidadeService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EstudoCasoPapUnico implements ValidationRule
{
    public function __construct(
        private readonly string $cursoTuteladoId,
        private readonly string $anoLectivoId,
        private readonly string $cursoClasseTurnoId,
        private readonly mixed $temaGrupo,
        private readonly ?string $grupoPapId = null,
    ) {}

    /**
     * Impede repetir o mesmo tema e estudo de caso em turnos diferentes do mesmo curso e ano lectivo.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! is_string($value)
            || trim($value) === ''
            || ! is_string($this->temaGrupo)
            || trim($this->temaGrupo) === ''
            || $this->cursoTuteladoId === ''
            || $this->anoLectivoId === ''
            || $this->cursoClasseTurnoId === ''
        ) {
            return;
        }

        if (app(TemaPapUnicidadeService::class)->mesmoTemaEEstudoCasoNoutroTurno(
            $this->cursoTuteladoId,
            $this->anoLectivoId,
            $this->cursoClasseTurnoId,
            $this->temaGrupo,
            $value,
            $this->grupoPapId,
        )) {
            $fail('Este tema já está associado ao mesmo estudo de caso noutro turno. Indique um estudo de caso diferente.');
        }
    }
}
