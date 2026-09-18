<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\UserProfile\UpdatePersonalProfile;
use App\Actions\Tenant\UserProfile\UpdateProfileAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\TwoFactorAuthenticationRequest;
use App\Http\Requests\Tenant\User\UpdatePersonalProfileRequest;
use App\Http\Requests\Tenant\User\UpdateProfileAvatarRequest;
use App\Models\Tenant\User;
use App\Services\Tenant\Users\UserProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class UserProfileController extends Controller
{
    /** Inicializa as dependências do perfil do usuário. */
    public function __construct(
        private readonly UpdatePersonalProfile $updatePersonalProfile,
        private readonly UpdateProfileAvatar $updateProfileAvatar,
        private readonly UserProfileService $profileService,
    ) {}

    /** Apresenta os dados pessoais do perfil. */
    public function show(User $user)
    {
        $this->authorizeOwnProfile($user);

        return Inertia::render('tenant/users/profile/show', [
            'user' => $this->profileService->profileData($user),
            'personalData' => $this->profileService->personalData($user),
        ]);
    }

    /** Actualiza os dados pessoais do próprio usuário. */
    public function updatePersonal(
        UpdatePersonalProfileRequest $request,
        User $user
    ): RedirectResponse {
        $this->authorizeOwnProfile($user);

        $this->updatePersonalProfile->handle($user, $request->validated());

        return back()->with('success', 'Dados pessoais actualizados com sucesso.');
    }

    /** Actualiza a fotografia do perfil. */
    public function updateAvatar(
        UpdateProfileAvatarRequest $request, User $user
    ): RedirectResponse {
        $this->authorizeOwnProfile($user);

        $this->updateProfileAvatar->handle($user, $request->file('avatar'));

        return back()->with('success', 'Foto de perfil actualizada com sucesso.');
    }

    /** Apresenta os dados académicos de um aluno. */
    public function academic(User $user)
    {
        $this->authorizeOwnProfile($user);
        abort_unless($user->hasRole('Aluno'), 404);

        return Inertia::render('tenant/users/profile/academic', [
            'user' => $this->profileService->profileData($user),
            'academicData' => $this->profileService->academicData($user),
        ]);
    }

    /** Apresenta as opções de segurança do perfil. */
    public function security(
        TwoFactorAuthenticationRequest $request,
        User $user
    ): Response {
        $this->authorizeOwnProfile($user);

        $props = $this->profileService->securityData($user);

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $user->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('tenant/users/profile/security', $props);
    }

    /** Garante que o usuário consulta apenas o próprio perfil. */
    private function authorizeOwnProfile(User $user): void
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = Auth::guard('tenant')->user();

        abort_unless($authenticatedUser?->is($user), 403);
    }
}
