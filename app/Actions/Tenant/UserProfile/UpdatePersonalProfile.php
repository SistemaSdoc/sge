<?php

namespace App\Actions\Tenant\UserProfile;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class UpdatePersonalProfile
{
    /**
     * Actualiza o usuário e o candidato associado numa transacção.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): void
    {
        $candidato = $user->aluno?->inscricao?->candidato;

        DB::transaction(function () use (
            $user,
            $candidato,
            $validated
        ): void {
            $user->fill([
                'nome' => $validated['nome'],
                'email' => $validated['email'],
                'telefone' => $validated['telefone'] ?? null,
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if (! $candidato) {
                return;
            }

            $filiacao = collect([
                $validated['nome_pai'] ?? null,
                $validated['nome_mae'] ?? null,
            ])->filter()->implode(' e ');

            $candidato->update([
                'nome' => $validated['nome'],
                'email' => $validated['email'],
                'bi' => $validated['bi'] ?? null,
                'genero' => $validated['genero'] ?? null,
                'data_nascimento' => $validated['data_nascimento'] ?? null,
                'nacionalidade' => $validated['nacionalidade'] ?? null,
                'naturalidade' => $validated['naturalidade'] ?? null,
                'filiacao' => $filiacao ?: null,
                'morada' => $validated['morada'] ?? null,
                'municipio' => $validated['municipio'] ?? null,
                'telefone' => $validated['telefone'] ?? null,
            ]);
        });
    }
}
