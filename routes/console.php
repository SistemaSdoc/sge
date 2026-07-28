<?php

use App\Services\AnoLectivoConsistencyService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(AnoLectivoConsistencyService::class)->sincronizar();
})->dailyAt('00:00')
    ->name('ano-lectivo:sincronizar')
    ->withoutOverlapping();
