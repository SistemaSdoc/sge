<?php

use App\Services\Tenant\Recibos\ReciboPdfService;
use Illuminate\Support\Facades\Storage;

it('reconhece apenas ficheiros com assinatura PDF', function () {
    Storage::fake('private');
    Storage::disk('private')->put('valido.pdf', '%PDF-1.7');
    Storage::disk('private')->put('invalido.pdf', '<html>erro</html>');

    $servico = new ReciboPdfService;

    expect($servico->existe('valido.pdf'))->toBeTrue()
        ->and($servico->existe('invalido.pdf'))->toBeFalse()
        ->and($servico->existe(null))->toBeFalse();
});
