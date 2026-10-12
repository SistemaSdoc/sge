<?php

namespace App\Actions\Central\Auth;

use App\Exceptions\UnauthorizedFacebookUserException;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;

class CompleteTenantFacebookAccountLink
{
    public function handle(string $state): void
    {
        if (blank($state)) {
            throw new UnauthorizedFacebookUserException;
        }

        $payload = json_decode(Crypt::decryptString($state), true);

        if (! is_array($payload) || ! isset($payload['tenant_id'], $payload['user_id'], $payload['exp'])) {
            throw new UnauthorizedFacebookUserException;
        }

        if ((int) $payload['exp'] < time()) {
            throw new UnauthorizedFacebookUserException;
        }

        $facebookId = (string) Socialite::driver('facebook')->user()->getId();
        $tenant = Tenant::query()->find($payload['tenant_id']);

        if (blank($facebookId) || $tenant === null || ! $tenant->canAccess()) {
            throw new UnauthorizedFacebookUserException;
        }

        $tenant->run(function () use ($facebookId, $payload): void {
            $tenantUser = User::query()->find($payload['user_id']);

            if ($tenantUser === null) {
                throw new UnauthorizedFacebookUserException;
            }

            if ($tenantUser->facebook_id !== null && $tenantUser->facebook_id !== $facebookId) {
                throw new UnauthorizedFacebookUserException;
            }

            $facebookIdAlreadyLinked = User::query()
                ->where('facebook_id', $facebookId)
                ->where('id', '!=', $tenantUser->getKey())
                ->exists();

            if ($facebookIdAlreadyLinked) {
                throw new UnauthorizedFacebookUserException;
            }

            $tenantUser->update(['facebook_id' => $facebookId]);
        });
    }
}
