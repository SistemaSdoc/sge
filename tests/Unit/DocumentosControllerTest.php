<?php

use App\Http\Controllers\DocumentosController;
use App\Services\CertificadoService;
use App\Services\DeclaracaoService;

it('injects the certificate and declaration services', function () {
    $declaracaoService = Mockery::mock(DeclaracaoService::class);
    $certificadoService = Mockery::mock(CertificadoService::class);

    $controller = new DocumentosController($declaracaoService, $certificadoService);

    $reflection = new ReflectionClass($controller);
    $declaracaoProp = $reflection->getProperty('declaracaoService');
    $certificadoProp = $reflection->getProperty('certificadoService');

    $declaracaoProp->setAccessible(true);
    $certificadoProp->setAccessible(true);

    expect($controller)->toBeInstanceOf(DocumentosController::class)
        ->and($declaracaoProp->getValue($controller))->toBe($declaracaoService)
        ->and($certificadoProp->getValue($controller))->toBe($certificadoService);
});
