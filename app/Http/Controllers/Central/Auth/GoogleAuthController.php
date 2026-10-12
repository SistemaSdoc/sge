<?php

namespace App\Http\Controllers\Central\Auth;

use App\Actions\Central\Auth\BeginTenantGoogleLogin;
use App\Actions\Central\Auth\CompleteTenantGoogleAccountLink;
use App\Actions\Central\Auth\CompleteTenantGoogleLogin;
use App\Enums\Auth\GoogleAuthError;
use App\Exceptions\UnauthorizedGoogleUserException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/**
 * Adapta o fluxo OAuth central às respostas HTTP e aos redirects do tenant.
 */
class GoogleAuthController extends Controller
{
    /**
     * @param  BeginTenantGoogleLogin  $beginTenantGoogleLogin  Inicia o OAuth após validar o tenant de origem.
     * @param  CompleteTenantGoogleLogin  $completeTenantGoogleLogin  Valida a identidade e emite o token tenant.
     * @param  CompleteTenantGoogleAccountLink  $completeTenantGoogleAccountLink  Associa a identidade ao utilizador atual.
     */
    public function __construct(
        private readonly BeginTenantGoogleLogin $beginTenantGoogleLogin,
        private readonly CompleteTenantGoogleLogin $completeTenantGoogleLogin,
        private readonly CompleteTenantGoogleAccountLink $completeTenantGoogleAccountLink,
    ) {}

    /**
     * Inicia o login Google para a origem tenant validada pela Action.
     */
    public function redirect(Request $request): RedirectResponse
    {
        return $this->beginTenantGoogleLogin->handle($request);
    }

    /**
     * Coordena a resposta do provider e converte cada falha num código seguro para o tenant.
     *
     * Cancelamento, erro do provider, conta recusada e state inválido recebem códigos distintos; falhas inesperadas
     * são registadas e recebem uma mensagem genérica. O contexto OAuth é removido em todos os caminhos.
     */
    public function callback(Request $request): RedirectResponse
    {
        $tenantLoginUrl = $request->session()->get('google_tenant_login_url');
        $isGoogleLink = filled($request->session()->get('google_link_state'));
        $googleLinkReturnUrl = $this->normalizeGoogleLinkReturnUrl(
            (string) $request->session()->get('google_link_return_url', '')
        );

        try {
            /** O utilizador cancelou o consentimento; regressa com o alerta de cancelamento. */
            if ($request->query('error') === 'access_denied') {
                if ($isGoogleLink) {
                    return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'cancelled');
                }

                return $this->redirectWithGoogleError($tenantLoginUrl, GoogleAuthError::DENIED);
            }

            /** Outros erros enviados pelo provider não devem ser passados ao browser como texto livre. */
            if ($request->filled('error')) {
                if ($isGoogleLink) {
                    return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'error');
                }

                return $this->redirectWithGoogleError($tenantLoginUrl, GoogleAuthError::FAILED);
            }

            if ($isGoogleLink) {
                $this->completeTenantGoogleAccountLink->handle((string) $request->session()->get('google_link_state'));

                return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'success');
            }

            $redirectTo = $this->completeTenantGoogleLogin->handle($request);

            return redirect()->to($redirectTo);

        } catch (UnauthorizedGoogleUserException) {
            /** Recusa esperada de conta/tenant; apresenta o alerta genérico sem detalhes internos. */
            if ($isGoogleLink) {
                return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'error');
            }

            return $this->redirectWithGoogleError($tenantLoginUrl, GoogleAuthError::ACCOUNT);

        } catch (InvalidStateException $e) {
            /** State OAuth inválido: regista aviso e devolve o alerta específico para nova tentativa. */
            Log::warning('[Google Auth Controller] InvalidStateException apanhada', [
                'message' => $e->getMessage(),
            ]);

            if ($isGoogleLink) {
                return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'error');
            }

            return $this->redirectWithGoogleError($tenantLoginUrl, GoogleAuthError::STATE);

        } catch (Throwable $e) {
            /** Falha inesperada: guarda detalhes apenas no log e mostra uma mensagem genérica ao browser. */
            Log::error('[Google Auth Controller] Exceção inesperada apanhada', [
                'class' => \get_class($e),
                'message' => $e->getMessage(),
            ]);

            if ($isGoogleLink) {
                return $this->redirectWithGoogleLinkResult($tenantLoginUrl, $googleLinkReturnUrl, 'error');
            }

            return $this->redirectWithGoogleError($tenantLoginUrl, GoogleAuthError::FAILED);
        } finally {
            /** Remove o contexto temporário tanto em sucesso como em qualquer ramo de erro. */
            $request->session()->forget([
                'google_tenant_id',
                'google_tenant_login_url',
                'google_link_state',
                'google_link_return_url',
            ]);
        }
    }

    private function redirectWithGoogleLinkResult(
        ?string $tenantLoginUrl,
        string $returnUrl,
        string $result
    ): RedirectResponse {
        if (blank($tenantLoginUrl)) {
            return redirect()->route('central.login');
        }

        $tenantBaseUrl = rtrim($tenantLoginUrl, '/');
        $returnUrl = $this->normalizeGoogleLinkReturnUrl($returnUrl);

        return redirect()->to("{$tenantBaseUrl}{$returnUrl}?google_link={$result}");
    }

    private function normalizeGoogleLinkReturnUrl(string $returnUrl): string
    {
        $returnUrl = trim($returnUrl);

        if ($returnUrl === '' || ! str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            return '/dashboard/settings/security';
        }

        return $returnUrl;
    }

    /**
     * Redirecciona para o tenant com um código allowlisted numa assinatura relativa temporária.
     *
     * A origem tem de vir da sessão validada no início OAuth. Sem esse contexto, usa-se o login central.
     */
    private function redirectWithGoogleError(
        ?string $tenantLoginUrl,
        GoogleAuthError $error
    ): RedirectResponse {
        /** Sem origem tenant guardada, não há destino cross-domain confiável; usa o login central. */
        if ($tenantLoginUrl === null) {
            return redirect()->route('central.login');
        }

        $signedLoginPath = URL::temporarySignedRoute(
            'tenant.google.error',
            now()->addMinutes(5),
            ['google_error' => $error->value],
            absolute: false,
        );

        $tenantBaseUrl = rtrim($tenantLoginUrl, '/');

        return redirect()->to("{$tenantBaseUrl}{$signedLoginPath}");
    }
}
