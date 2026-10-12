<?php

namespace App\Http\Controllers\Central\Auth;

use App\Actions\Central\Auth\BeginTenantFacebookLogin;
use App\Actions\Central\Auth\CompleteTenantFacebookAccountLink;
use App\Actions\Central\Auth\CompleteTenantFacebookLogin;
use App\Enums\Auth\FacebookAuthError;
use App\Exceptions\UnauthorizedFacebookUserException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class FacebookAuthController extends Controller
{
    public function __construct(
        private readonly BeginTenantFacebookLogin $beginTenantFacebookLogin,
        private readonly CompleteTenantFacebookLogin $completeTenantFacebookLogin,
        private readonly CompleteTenantFacebookAccountLink $completeTenantFacebookAccountLink,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        return $this->beginTenantFacebookLogin->handle($request);
    }

    public function callback(Request $request): RedirectResponse
    {
        $tenantLoginUrl = $request->session()->get('facebook_tenant_login_url');
        $isAccountLink = filled($request->session()->get('facebook_link_state'));
        $returnUrl = $this->normalizeLinkReturnUrl(
            (string) $request->session()->get('facebook_link_return_url', '')
        );

        try {
            if ($request->query('error') === 'access_denied') {
                if ($isAccountLink) {
                    return $this->redirectWithLinkResult(
                        $tenantLoginUrl,
                        $returnUrl,
                        'cancelled'
                    );
                }

                return $this->redirectWithFacebookError($tenantLoginUrl, FacebookAuthError::DENIED);
            }

            if ($request->filled('error')) {
                if ($isAccountLink) {
                    return $this->redirectWithLinkResult(
                        $tenantLoginUrl,
                        $returnUrl,
                        'error'
                    );
                }

                return $this->redirectWithFacebookError(
                    $tenantLoginUrl,
                    FacebookAuthError::FAILED
                );
            }

            if ($isAccountLink) {
                $this->completeTenantFacebookAccountLink->handle(
                    (string) $request->session()->get('facebook_link_state'),
                );

                return $this->redirectWithLinkResult(
                    $tenantLoginUrl,
                    $returnUrl,
                    'success'
                );
            }

            return redirect()->to($this->completeTenantFacebookLogin->handle($request));
        } catch (UnauthorizedFacebookUserException) {
            if ($isAccountLink) {
                return $this->redirectWithLinkResult(
                    $tenantLoginUrl,
                    $returnUrl, 'error'
                );
            }

            return $this->redirectWithFacebookError(
                $tenantLoginUrl,
                FacebookAuthError::ACCOUNT
            );
        } catch (InvalidStateException $exception) {
            Log::warning('[Facebook Auth Controller] InvalidStateException apanhada', [
                'message' => $exception->getMessage(),
            ]);

            if ($isAccountLink) {
                return $this->redirectWithLinkResult(
                    $tenantLoginUrl,
                    $returnUrl,
                    'error'
                );
            }

            return $this->redirectWithFacebookError(
                $tenantLoginUrl,
                FacebookAuthError::STATE
            );
        } catch (Throwable $exception) {
            Log::error('[Facebook Auth Controller] Falha na autenticação', [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if ($isAccountLink) {
                return $this->redirectWithLinkResult(
                    $tenantLoginUrl,
                    $returnUrl,
                    'error'
                );
            }

            return $this->redirectWithFacebookError(
                $tenantLoginUrl,
                FacebookAuthError::FAILED
            );
        } finally {
            $request->session()->forget([
                'facebook_tenant_id',
                'facebook_tenant_login_url',
                'facebook_link_state',
                'facebook_link_return_url',
            ]);
        }
    }

    private function redirectWithLinkResult(
        ?string $tenantLoginUrl,
        string $returnUrl,
        string $result
    ): RedirectResponse {
        if (blank($tenantLoginUrl)) {
            return redirect()->route('central.login');
        }

        $tenantBaseUrl = rtrim($tenantLoginUrl, '/');
        $returnUrl = $this->normalizeLinkReturnUrl($returnUrl);
        $querySeparator = str_contains($returnUrl, '?') ? '&' : '?';

        return redirect()->to("{$tenantBaseUrl}{$returnUrl}{$querySeparator}facebook_link={$result}");
    }

    private function normalizeLinkReturnUrl(string $returnUrl): string
    {
        $returnUrl = trim($returnUrl);

        if ($returnUrl === '' || ! str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            return '/dashboard/settings/security';
        }

        return $returnUrl;
    }

    private function redirectWithFacebookError(
        ?string $tenantLoginUrl,
        FacebookAuthError $error
    ): RedirectResponse {
        if (blank($tenantLoginUrl)) {
            return redirect()->route('central.login');
        }

        $signedLoginPath = URL::temporarySignedRoute(
            'tenant.facebook.error',
            now()->addMinutes(5),
            ['facebook_error' => $error->value],
            absolute: false,
        );

        return redirect()->to(rtrim($tenantLoginUrl, '/').$signedLoginPath);
    }
}
