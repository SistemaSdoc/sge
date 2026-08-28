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
     * Display the register view.
     */
    public function create()
    {
        return inertia('central/auth/register', [
            'passwordRules' => $this->passwordRules(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterStoreRequest $request)
    {
        $tenant = $this->service->register($request->validated());

        $domain = 'http://'.$tenant->domains->first()->domain;

        return Inertia::location($domain);
    }
}
