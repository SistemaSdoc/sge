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
    ->withoutOverlapping();

// Corre todos os dias às 23:55, só em produção
// Schedule::command(FinalizarPautasVencidas::class)
//     ->dailyAt('23:55')
//     ->environments(['production'])
//     ->withoutOverlapping()         // evita correr duas vezes em simultâneo
//     ->onOneServer()                // se tiveres múltiplos servidores
//     ->appendOutputTo(storage_path('logs/pautas-finalizadas.log'));

// Corre local em Desenvolvimento
Schedule::command(FinalizarPautasVencidas::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/pautas-finalizadas.log'));