<?php

namespace App\Actions\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Facades\DB;

class DeleteGrupoPap
{
    /**
     * Remove os elementos, jurados e o grupo PAP numa única transacção.
     */
    public function handle(GrupoPap $grupoPap): void
    {
        DB::transaction(function () use ($grupoPap): void {
            $grupoPap->elementos()->delete();
            $grupoPap->jurados()->delete();
            $grupoPap->delete();
        });
    }
}
