<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Facades\DB;

/**
 * Remove um grupo PAP e os seus registos dependentes.
 */
class DeleteGrupoPap
{
    public function handle(GrupoPap $grupoPap): void
    {
        DB::transaction(function () use ($grupoPap): void {
            $grupoPap->elementos()->delete();

            $grupoPap->jurados()->delete();

            $grupoPap->delete();
        });
    }
}
