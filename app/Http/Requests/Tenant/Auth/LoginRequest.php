<?php

namespace App\Http\Requests\Tenant\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'email.string' => 'O email deve ser um texto válido.',
            'email.email' => 'Introduza um endereço de email válido.',
            'password.required' => 'A palavra-passe é obrigatória.',
            'password.string' => 'A palavra-passe deve ser um texto válido.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard('tenant')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'O email ou a palavra-passe estão incorretos.',
            ]);
        }

        if (Auth::guard('tenant')->user()?->temInscricaoCancelada()) {
            Auth::guard('tenant')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'O email ou a palavra-passe estão incorretos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $unit = $seconds === 1 ? 'segundo' : 'segundos';

        throw ValidationException::withMessages([
            'email' => "Demasiadas tentativas de início de sessão. Tente novamente dentro de {$seconds} {$unit}.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
