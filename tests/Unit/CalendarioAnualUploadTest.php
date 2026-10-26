<?php

use App\Actions\Central\CalendarioAnual\UploadCalendarioAnual;
use App\Models\Central\CalendarioAnual;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('guarda um calendário anual e substitui o ficheiro anterior', function (): void {
    Storage::fake(config('filesystems.default'));

    $calendario = Mockery::mock(CalendarioAnual::class)->makePartial();
    $calendario->setIncrementing(false);
    $calendario->setKeyType('string');
    $calendario->setAttribute('id', 'calendario-uuid');
    $calendario->setAttribute('ficheiro_path', 'calendarios-anuais/calendario-uuid/antigo.pdf');
    Storage::disk(config('filesystems.default'))->put($calendario->ficheiro_path, '%PDF-1.7');
    $calendario->shouldReceive('save')->once();

    $upload = app(UploadCalendarioAnual::class);
    $upload->handle($calendario, UploadedFile::fake()->createWithContent('calendario.pdf', '%PDF-1.7'));

    $caminhoNovo = $calendario->ficheiro_path;

    expect($calendario->ficheiro_nome)->toBe('calendario.pdf')
        ->and($caminhoNovo)->toStartWith('calendarios-anuais/calendario-uuid/');
    Storage::disk(config('filesystems.default'))->assertMissing('calendarios-anuais/calendario-uuid/antigo.pdf');
    Storage::disk(config('filesystems.default'))->assertExists($caminhoNovo);
});

test('mantém o ficheiro antigo até ao commit da transação', function (): void {
    Storage::fake(config('filesystems.default'));

    $afterCommit = null;
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('transactionLevel')->once()->andReturn(1);
    $connection->shouldReceive('afterCommit')
        ->once()
        ->with(Mockery::on(function (callable $callback) use (&$afterCommit): bool {
            $afterCommit = $callback;

            return true;
        }));
    $connection->shouldReceive('afterRollBack')->once();

    $calendario = Mockery::mock(CalendarioAnual::class)->makePartial();
    $calendario->setIncrementing(false);
    $calendario->setKeyType('string');
    $calendario->setAttribute('id', 'calendario-uuid');
    $calendario->setAttribute('ficheiro_path', 'calendarios-anuais/calendario-uuid/antigo.pdf');
    $calendario->shouldReceive('getConnection')->once()->andReturn($connection);
    $calendario->shouldReceive('save')->once();
    Storage::disk(config('filesystems.default'))->put($calendario->ficheiro_path, '%PDF-1.7');

    app(UploadCalendarioAnual::class)->handle(
        $calendario,
        UploadedFile::fake()->createWithContent('calendario.pdf', '%PDF-1.7')
    );

    $caminhoNovo = $calendario->ficheiro_path;

    Storage::disk(config('filesystems.default'))->assertExists('calendarios-anuais/calendario-uuid/antigo.pdf');
    $afterCommit();
    Storage::disk(config('filesystems.default'))->assertMissing('calendarios-anuais/calendario-uuid/antigo.pdf');
    Storage::disk(config('filesystems.default'))->assertExists($caminhoNovo);
});

test('remove o ficheiro novo quando a transação sofre rollback', function (): void {
    Storage::fake(config('filesystems.default'));

    $afterRollback = null;
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('transactionLevel')->once()->andReturn(1);
    $connection->shouldReceive('afterCommit')->never();
    $connection->shouldReceive('afterRollBack')
        ->once()
        ->with(Mockery::on(function (callable $callback) use (&$afterRollback): bool {
            $afterRollback = $callback;

            return true;
        }));

    $calendario = Mockery::mock(CalendarioAnual::class)->makePartial();
    $calendario->setIncrementing(false);
    $calendario->setKeyType('string');
    $calendario->setAttribute('id', 'calendario-uuid');
    $calendario->setAttribute('ficheiro_path', '');
    $calendario->shouldReceive('getConnection')->once()->andReturn($connection);
    $calendario->shouldReceive('save')->once();

    app(UploadCalendarioAnual::class)->handle(
        $calendario,
        UploadedFile::fake()->createWithContent('calendario.pdf', '%PDF-1.7')
    );

    $caminhoNovo = $calendario->ficheiro_path;

    Storage::disk(config('filesystems.default'))->assertExists($caminhoNovo);
    $afterRollback();
    Storage::disk(config('filesystems.default'))->assertMissing($caminhoNovo);
});
