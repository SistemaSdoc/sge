<?php

use App\Services\AnoLectivoConsistencyService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\FinalizarPautasVencidas;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(AnoLectivoConsistencyService::class)->sincronizar();
})->dailyAt('00:00')
    ->name('ano-lectivo:sincronizar')
    ->withoutOverlapping();


// Corre todos os dias às 23:55, só em produção
Schedule::command(FinalizarPautasVencidas::class)
    ->dailyAt('23:55')
    ->environments(['production'])
    ->withoutOverlapping()         // evita correr duas vezes em simultâneo
    ->onOneServer()                // se tiveres múltiplos servidores
    ->appendOutputTo(storage_path('logs/pautas-finalizadas.log'));