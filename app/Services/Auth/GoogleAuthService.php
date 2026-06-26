<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    public function redirect(): RedirectResponse
    {
        Log::debug('[Google Auth] [0] redirect() chamado');

        return Socialite::driver('google')->redirect();
    }

    public function handleCallback(): User
    {
        Log::debug('[Google Auth] [1] handleCallback() iniciado', [
            'session_id' => session()->getId(),
            'has_code' => request()->has('code'),
            'has_state' => request()->has('state'),
            'has_error' => request()->has('error'),
            'error' => request()->get('error'),
        ]);

        try {
            $socialUser = Socialite::driver('google')->user();
            Log::debug('[Google Auth] [2] Socialite->user() resolvido com sucesso');

        } catch (InvalidStateException $e) {
            Log::error('[Google Auth] [2-ERR] InvalidStateException', [
                'message' => $e->getMessage(),
                'session_id' => session()->getId(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[Google Auth] [2-ERR] Exceção inesperada no Socialite', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $googleId = $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName();
        $avatar = $socialUser->getAvatar();

        Log::debug('[Google Auth] [3] Dados do utilizador Google obtidos', [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name,
        ]);

        try {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'nome' => $name,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                ]
            );
            Log::debug('[Google Auth] [4] updateOrCreate concluído', [
                'user_id' => $user->id,
                'was_recently_created' => $user->wasRecentlyCreated,
            ]);
        } catch (\Exception $e) {
            Log::error('[Google Auth] [4-ERR] Falha no updateOrCreate', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        if ($user->wasRecentlyCreated) {
            Log::debug('[Google Auth] [5] Novo utilizador — a atribuir role Candidato');

            $candidatoRole = Role::where('nome', 'Candidato')->first();

            Log::debug('[Google Auth] [5a] Role Candidato', [
                'found' => (bool) $candidatoRole,
                'role_id' => $candidatoRole?->id,
            ]);

            if ($candidatoRole && $user->roles()->doesntExist()) {
                $user->roles()->attach($candidatoRole->id, [
                    'id' => (string) Str::uuid7(),
                ]);
                Log::debug('[Google Auth] [5b] Role Candidato atribuído');
            }
        } else {
            Log::debug('[Google Auth] [5] Utilizador existente — sem atribuição de role');
        }

        Log::debug('[Google Auth] [6] A chamar Auth::login()', ['user_id' => $user->id]);

        try {
            Auth::login($user);
            Log::debug('[Google Auth] [7] Auth::login() concluído', [
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[Google Auth] [7-ERR] Falha no Auth::login()', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::debug('[Google Auth] [8] handleCallback() concluído — a retornar user');

        return $user;
    }
}
