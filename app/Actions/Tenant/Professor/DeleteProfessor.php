<?php

namespace App\Actions\Tenant\Professor;

use App\Models\Tenant\Professor;

class DeleteProfessor
{
    /**
     * Remove o perfil de professor, preservando o comportamento actual da aplicação.
     */
    public function handle(Professor $professor): void
    {
        $professor->delete();
    }
}
