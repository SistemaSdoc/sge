<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Tenant\Aluno;
use Illuminate\Support\Facades\DB;

/** Remove o aluno e limpa os registos associados dentro de uma transação. */
class DeleteAluno
{
    public function handle(Aluno $aluno): void
    {
        DB::transaction(function () use ($aluno): void {
            $user = $aluno->user;
            $candidato = $aluno->inscricao?->candidato;

            $aluno->cleanupOnUserRemoval();
            $candidato?->delete();
            $user?->removeRole('Aluno');
        });
    }
}
