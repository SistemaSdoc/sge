<?php

namespace App\Actions\Tenant\ElementoGrupoPap;

use App\Models\Tenant\ElementoGrupoPap;

class DeleteElementoGrupoPap
{
    /**
     * Remove um aluno do grupo PAP.
     */
    public function handle(ElementoGrupoPap $elementoGrupoPap): void
    {
        $elementoGrupoPap->delete();
    }
}
