<?php

namespace App\Actions\Tenant\Professor;

use App\Models\Tenant\Professor;
use Illuminate\Support\Facades\DB;

class DeleteProfessor
{
    public function handle(Professor $professor): void
    {
        DB::transaction(function () use ($professor): void {
            $professor->cleanupOnUserRemoval();
        });
    }
}
