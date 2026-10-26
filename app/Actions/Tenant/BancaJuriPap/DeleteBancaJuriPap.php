<?php

namespace App\Actions\Tenant\BancaJuriPap;

use App\Models\Tenant\BancaJuriPap;

class DeleteBancaJuriPap
{
    /**
     * Remove um jurado da banca.
     */
    public function handle(BancaJuriPap $bancaJuriPap): void
    {
        $bancaJuriPap->delete();
    }
}
