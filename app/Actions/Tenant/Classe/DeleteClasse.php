<?php

namespace App\Actions\Tenant\Classe;

use App\Models\Tenant\Classe;

class DeleteClasse
{
    /**
     * Remove uma classe do tenant actual.
     */
    public function handle(Classe $classe): void
    {
        $classe->delete();
    }
}
