<?php

namespace Tests\Unit;

use App\Http\Controllers\ProgressaoController;
use App\Models\AnoLectivo;
use App\Services\AprovacaoService;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressaoControllerTest extends TestCase
{
    #[Test]
    public function test_resolve_ano_lectivo_value_uses_the_start_year_of_the_selected_ano_lectivo(): void
    {
        $controller = new ProgressaoController(app(AprovacaoService::class));
        $method = new \ReflectionMethod($controller, 'resolveAnoLectivoValue');
        $method->setAccessible(true);

        $anoLectivo = new AnoLectivo();
        $anoLectivo->forceFill([
            'data_inicio' => '2025-01-01',
            'data_fim' => '2025-12-31',
        ]);

        $this->assertSame(2025, $method->invoke($controller, $anoLectivo));
    }
}
