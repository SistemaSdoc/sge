<?php

namespace App\Actions\Tenant\Professor;

use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use App\Notifications\Professor\ProfessorCriadoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateProfessor
{
    private const INITIAL_PASSWORD = '123456';

    /**
     * Cria a conta de usuario, atribui a role Professor e cria o perfil académico.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $actor, array $validated): Professor
    {
        return DB::transaction(function () use ($actor, $validated): Professor {
            $user = User::create([
                'nome' => $validated['nome'],
                'email' => $validated['email'],
                'bi' => $validated['bi'],
                'telefone' => $validated['telefone'],
                'password' => Hash::make(self::INITIAL_PASSWORD),
                'instituicao_id' => $actor->instituicao_id,
            ]);

            $role = Role::query()
                ->where('name', 'Professor')
                ->where('guard_name', 'tenant')
                ->firstOrFail();

            $user->assignRole($role);

            $professor = Professor::create([
                'user_id' => $user->getKey(),
                'especialidade' => $validated['especialidade'] ?? null,
                'nivel_academico' => $validated['nivel_academico'] ?? null,
            ]);

            $user->notify(new ProfessorCriadoNotification($user, self::INITIAL_PASSWORD));

            return $professor->load('user');
        });
    }
}
