<?php

namespace App\Actions\Tenant\User;

use App\Exceptions\UserRemovalBlockedException;
use App\Models\Tenant\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function handle(User $user): void
    {
        try {
            DB::transaction(function () use ($user): void {
                foreach ($user->profiles() as $profile) {
                    $profile->cleanupOnUserRemoval();
                }

                $user->cursosSecretariados()->detach();
                $user->syncRoles([]);
                $user->syncPermissions([]);
                $user->forceDelete();
            });
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'foreign key')) {
                throw $exception;
            }

            throw new UserRemovalBlockedException(
                'Não é possível remover este usuário porque existem registos históricos associados.'
            );
        }
    }
}
