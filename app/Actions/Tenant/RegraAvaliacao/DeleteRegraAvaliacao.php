<?php

namespace App\Actions\Tenant\RegraAvaliacao;

use App\Models\Tenant\RegraAvaliacao;

class DeleteRegraAvaliacao
{
    /**
     * Remove uma regra de avaliação.
     */
    public function handle(RegraAvaliacao $regraAvaliacao): void
    {
        $regraAvaliacao->delete();
    }
}
