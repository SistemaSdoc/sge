<?php

namespace App\Actions\Central\Auth;

use App\Exceptions\UnauthorizedFacebookUserException;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Throwable;

class CompleteTenantFacebookLogin
{
    public function handle(Request $request): string
    {
        $facebookUser = Socialite::driver('facebook')->user();
        $facebookId = (string) $facebookUser->getId();
        $tenantId = $request->session()->get('facebook_tenant_id');
        $tenantOrigin = (string) $request->session()->get('facebook_tenant_login_url');

        if (blank($facebookId) || blank($tenantId) || blank($tenantOrigin)) {
            throw new UnauthorizedFacebookUserException;
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null || ! $tenant->canAccess()) {
            throw new UnauthorizedFacebookUserException;
        }

        try {
            $tenantUser = $tenant->run(fn (): ?User => User::query()
                ->where('facebook_id', $facebookId)
                ->first());
        } catch (Throwable $exception) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            throw $exception;
        }

        if ($tenantUser === null) {
            throw new UnauthorizedFacebookUserException;
        }

        $tenantBaseUrl = rtrim($tenantOrigin, '/');
        $token = ImpersonationToken::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $tenantUser->getKey(),
            'redirect_url' => "{$tenantBaseUrl}/dashboard",
            'auth_guard' => 'tenant',
        ]);

        return "{$tenantBaseUrl}/token/{$token->token}";
    }
}
