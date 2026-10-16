<?php

namespace App\Rules;

use App\Models\Tenant\GrupoPap;
use App\Services\Tenant\TemaPapUnicidadeService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TemaPapUnico implements ValidationRule
{
    public function __construct(
        private readonly string $cursoTuteladoId,
        private readonly string $anoLectivoId,
        private readonly string $cursoClasseTurnoId,
        private readonly ?string $grupoPapId = null,
        private readonly ?string $estudoCaso = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! is_string($value)
            || $this->cursoTuteladoId === ''
            || $this->anoLectivoId === ''
            || $this->cursoClasseTurnoId === ''
        ) {
            return;
        }

        if (app(TemaPapUnicidadeService::class)->temaExiste(
            $this->cursoTuteladoId,
            $this->anoLectivoId,
            $this->cursoClasseTurnoId,
            (string) $value,
            $this->grupoPapId,
        )) {
            $fail('Este tema já foi escolhido por outro grupo neste turno e ano lectivo.');

            return;
        }

        $estudoCaso = request()->exists('estudo_caso')
            ? request()->input('estudo_caso')
            : ($this->grupoPapId
                ? GrupoPap::query()->whereKey($this->grupoPapId)->value('estudo_caso')
                : $this->estudoCaso);
        $estudoCaso = is_string($estudoCaso) ? $estudoCaso : null;

        if (app(TemaPapUnicidadeService::class)->mesmoTemaEEstudoCasoNoutroTurno(
            $this->cursoTuteladoId,
            $this->anoLectivoId,
            $this->cursoClasseTurnoId,
            (string) $value,
            $estudoCaso,
            $this->grupoPapId,
        )) {
            $fail('Este tema já está associado ao mesmo estudo de caso noutro turno. Indique um estudo de caso diferente.');
        }
    }
}
