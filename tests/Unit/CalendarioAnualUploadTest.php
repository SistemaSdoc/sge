<?php

use App\Actions\Central\CalendarioAnual\UploadCalendarioAnual;
use App\Models\Central\CalendarioAnual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('guarda um calendário anual e substitui o ficheiro anterior', function (): void {
    Storage::fake('public');

    $calendario = Mockery::mock(CalendarioAnual::class)->makePartial();
    $calendario->setIncrementing(false);
    $calendario->setKeyType('string');
    $calendario->setAttribute('id', 'calendario-uuid');
    $calendario->setAttribute('ficheiro_path', 'calendarios-anuais/calendario-uuid/antigo.pdf');
    Storage::disk('public')->put($calendario->ficheiro_path, '%PDF-1.7');
    $calendario->shouldReceive('save')->once();

    $upload = app(UploadCalendarioAnual::class);
    $upload->handle($calendario, UploadedFile::fake()->createWithContent('calendario.pdf', '%PDF-1.7'));

    $caminhoNovo = $calendario->ficheiro_path;

    expect($calendario->ficheiro_nome)->toBe('calendario.pdf')
        ->and($caminhoNovo)->toStartWith('calendarios-anuais/calendario-uuid/');
    Storage::disk('public')->assertMissing('calendarios-anuais/calendario-uuid/antigo.pdf');
    Storage::disk('public')->assertExists($caminhoNovo);
});
