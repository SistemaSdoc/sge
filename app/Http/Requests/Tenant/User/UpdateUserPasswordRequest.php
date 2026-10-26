<?php

namespace App\Http\Requests\Tenant\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a autorização é feita no controller via Gate
    }

    public function rules(): array
    {
        $isSelf = Auth::guard('tenant')->id() === $this->route('user')->id;

        return [
            'current_password' => $isSelf
                ? ['required', 'current_password:tenant']
                : ['prohibited'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'palavra-passe actual',
            'password' => 'nova palavra-passe',
        ];
    }
}