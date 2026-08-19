<?php

namespace App\Http\Controllers\Central\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\RegisterStoreRequest;
use App\Services\Central\Auth\RegisterService;
use Inertia\Inertia;

class RegisteredController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private RegisterService $service) {}

    /**
     * Display the institution registration view.
     */
    public function create()
    {
        return inertia('central/auth/register', [
            'passwordRules' => $this->passwordRules(),
        ]);
    }

    /**
     * Handle an incoming institution registration request.
     */
    public function store(RegisterStoreRequest $request)
    {
        $redirectUrl = $this->service->register($request->validated());

        return Inertia::location($redirectUrl);
    }
}
