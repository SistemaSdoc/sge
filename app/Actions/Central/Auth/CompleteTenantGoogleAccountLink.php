<?php

namespace App\Actions\Central\Auth;

use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

class CompleteTenantGoogleAccountLink
{
    public function handle(string $state): void
    {
        if (blank($state)) {
            throw new RuntimeException('Dados da associação Google incompletos.');
        }

        $googleId = (string) Socialite::driver('google')->user()->getId();

        if (blank($googleId)) {
            throw new RuntimeException('Identidade Google ausente.');
        }

        $payload = json_decode(Crypt::decryptString($state), true);

        if (! is_array($payload) || ! isset($payload['tenant_id'], $payload['user_id'], $payload['exp'])) {
            throw new RuntimeException('Estado de associação Google inválido.');
        }

        if ((int) $payload['exp'] < time()) {
            throw new RuntimeException('Estado de associação Google expirado.');
        }

        $tenant = Tenant::query()->find($payload['tenant_id']);

        if ($tenant === null || ! $tenant->canAccess()) {
            throw new RuntimeException('Tenant inválido para associação Google.');
        }

        $tenant->run(function () use ($payload, $googleId): void {
            $tenantUser = User::query()->find($payload['user_id']);

            if ($tenantUser === null) {
                throw new RuntimeException('Utilizador do tenant não encontrado para associação Google.');
            }

            if ($tenantUser->google_id !== null && $tenantUser->google_id !== $googleId) {
                throw new RuntimeException('Esta conta já está associada a outra conta Google.');
            }

            $googleIdAlreadyLinked = User::query()
                ->where('google_id', $googleId)
                ->where('id', '!=', $tenantUser->getKey())
                ->exists();

            if ($googleIdAlreadyLinked) {
                throw new RuntimeException('Esta conta Google já está associada a outro utilizador.');
            }

            $tenantUser->update(['google_id' => $googleId]);
        });
    }
}
