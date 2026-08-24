<?php

namespace App\Http\Controllers\Central\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\RegisterStoreRequest;
use App\Services\Central\Auth\RegisterService;

class RegisteredController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private RegisterService $service) {}

    public function create()
    {
        return inertia('central/auth/register', [
            'passwordRules' => $this->passwordRules(),
        ]);
    }

    public function store(RegisterStoreRequest $request)
    {
        $this->service->register($request->validated());

        return redirect()->route('central.register.pending');
    }
}