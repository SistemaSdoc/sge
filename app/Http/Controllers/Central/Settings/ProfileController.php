<?php

namespace App\Http\Controllers\Central\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Settings\ProfileDeleteRequest;
use App\Http\Requests\Central\Settings\ProfileUpdateRequest;
use App\Models\Central\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();

        return Inertia::render('tenant/settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('central.dashboard.profile.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => __('Profile updated.'),
            ]);
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();

        Auth::guard('web')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
