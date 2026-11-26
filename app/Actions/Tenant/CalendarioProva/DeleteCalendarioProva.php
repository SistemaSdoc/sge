<?php

namespace App\Actions\Tenant\CalendarioProva;

use App\Models\Tenant\CalendarioProva;
use Illuminate\Support\Facades\DB;

class DeleteCalendarioProva
{
    public function handle(CalendarioProva $calendario): void
    {
        DB::transaction(function () use ($calendario): void {
            $calendario->entradas()->delete();
            $calendario->delete();
        });
    }
}
