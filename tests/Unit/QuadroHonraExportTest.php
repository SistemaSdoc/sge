<?php

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Http\Controllers\Tenant\QuadroHonraController;
use App\Models\Tenant\QuadroHonra;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\QuadroHonraPdfService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

uses(TestCase::class);

it('does not export a quadro without eligible students', function () {
    $quadroHonra = new class extends QuadroHonra
    {
        public function itens(): HasMany
        {
            $items = Mockery::mock(HasMany::class);
            $items->shouldReceive('exists')->once()->andReturnFalse();

            return $items;
        }
    };

    $pdfService = Mockery::mock(QuadroHonraPdfService::class);
    $pdfService->shouldNotReceive('download');

    $controller = new class(Mockery::mock(GerarQuadroHonra::class), Mockery::mock(CalcularCandidatosQuadroHonra::class), $pdfService) extends QuadroHonraController
    {
        public function authorize($ability, $arguments = [])
        {
            return true;
        }
    };

    expect(fn () => $controller->exportar($quadroHonra))
        ->toThrow(NotFoundHttpException::class);
});
