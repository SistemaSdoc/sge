<?php

use App\Models\Tenant\CalendarioProva;

it('clears trimester for non-trimestral calendar types', function (string $tipo): void {
    expect(CalendarioProva::trimestreForTipo($tipo, '1'))->toBeNull();
})->with([
    CalendarioProva::TIPO_RECURSO,
    CalendarioProva::TIPO_EXAME_ESPECIAL,
]);

it('preserves the selected trimester for trimestral calendars', function (): void {
    expect(CalendarioProva::trimestreForTipo(CalendarioProva::TIPO_TRIMESTRAL, '1'))->toBe(1);
});
