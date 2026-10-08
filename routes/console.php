<?php

use App\Console\Commands\FinalizarPautasVencidas;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('anoletivo:sincronizar')
    ->everyMinute()
    ->name('ano-lectivo:sincronizar')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/ano-lectivo.log'));

Schedule::command('tenants:sync-nomes')->daily();

Schedule::command(FinalizarPautasVencidas::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/pautas-finalizadas.log'));

Schedule::command('prazos:verificar')->everyFiveMinutes();
