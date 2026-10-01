<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Tenant\Candidato;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class CompleteStudentProfile
{
    /**
     * Salva os dados obrigatórios do candidato e sincroniza o telefone da conta.
     */
    public function handle(User $user, Candidato $candidato, array $validated): void
    {
        DB::transaction(function () use ($user, $candidato, $validated): void {
            $candidato->update([
                'telefone' => $validated['telefone'],
                'morada' => $validated['morada'],
                'genero' => $validated['genero'],
                'nacionalidade' => $validated['nacionalidade'],
                'naturalidade' => $validated['naturalidade'],
                'filiacao' => $validated['nome_pai'].' e '.$validated['nome_mae'],
                'data_nascimento' => $validated['data_nascimento'],
                'municipio' => $validated['municipio'],
                'perfil_completo' => true,
            ]);

            $user->update(['telefone' => $validated['telefone']]);
        });
    }
}
