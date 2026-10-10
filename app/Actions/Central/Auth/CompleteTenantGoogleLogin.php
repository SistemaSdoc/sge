<?php

namespace App\Actions\Central\Auth;

use App\Exceptions\UnauthorizedGoogleUserException;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Throwable;

/** Completa o callback Google e autentica o utilizador no tenant através de um token único. */
class CompleteTenantGoogleLogin
{
    /**
     * Confirma a identidade Google, valida o contexto tenant e devolve o URL para consumir o token.
     *
     * @throws UnauthorizedGoogleUserException Se o provider, o utilizador ou o tenant não forem elegíveis.
     * @throws InvalidStateException Se o state OAuth não corresponder à sessão inicial.
     * @throws Throwable Se o provider ou a persistência falharem inesperadamente.
     */
    public function handle(Request $request): string
    {
        /** Troca o code pelo perfil e valida o state OAuth da sessão. */
        $googleUser = Socialite::driver('google')->user();

        /** Este fluxo espera o perfil OAuth 2 do driver Google. */
        if (! ($googleUser instanceof GoogleUser)) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Lê o claim de verificação, disponível apenas no perfil bruto do provider. */
        $googleProfile = $googleUser->getRaw();
        $emailVerified = $googleProfile['email_verified'] ?? $googleProfile['verified_email'] ?? false;

        /** Email não confirmado: recusa antes de consultar ou alterar utilizadores. */
        if ($emailVerified !== true) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Recupera o tenant guardado após validar o domínio no início do OAuth. */
        $tenantId = $request->session()->get('google_tenant_id');

        /** Sem o ID, recusa; o controller usa a origem tenant se ainda estiver na sessão. */
        if (blank($tenantId)) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Resolve os metadados do tenant na base central. */
        $tenant = Tenant::query()->find($tenantId);

        /** Tenant inexistente ou sem acesso: recusa sem emitir token. */
        if ($tenant === null || ! $tenant->canAccess()) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Recupera a origem validada no redirect inicial. */
        $tenantOrigin = (string) $request->session()->get('google_tenant_login_url');

        /** Sem origem validada não há redirect cross-domain seguro. */
        if (blank($tenantOrigin)) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Executa lookup e vínculo na conexão do tenant. */
        try {
            $tenantUser = $tenant->run(function () use ($googleUser): ?User {
                /** Procura o utilizador existente pelo email; não cria contas neste fluxo. */
                $tenantUser = User::query()
                    ->where('email', $googleUser->getEmail())
                    ->first();

                /** Liga uma conta sem Google ID; vínculos existentes não são alterados. */
                if ($tenantUser !== null && $tenantUser->google_id === null) {
                    $tenantUser->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }

                return $tenantUser;
            });
        } catch (Throwable $exception) {
            /** Limpa o contexto tenant antes de propagar falhas da callback. */
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            throw $exception;
        }

        /** Email sem utilizador neste tenant: recusa sem criar conta nem token. */
        if ($tenantUser === null) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Outro Google ID já ligado: preserva o vínculo e recusa o acesso. */
        if (
            $tenantUser->google_id !== null &&
            $tenantUser->google_id !== $googleUser->getId()
        ) {
            throw new UnauthorizedGoogleUserException;
        }

        /** Cria o token temporário de impersonation para este utilizador e tenant. */
        $tenantBaseUrl = rtrim($tenantOrigin, '/');

        $token = ImpersonationToken::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $tenantUser->getKey(),
            'redirect_url' => "{$tenantBaseUrl}/dashboard",
            'auth_guard' => 'tenant',
        ]);

        /** A rota tenant consome o token e redirecciona para o dashboard. */
        return "{$tenantBaseUrl}/token/{$token->token}";
    }
}
