<?php

namespace App\Http\Requests\Tenant\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => $this->passwordRules(),
        ];

        // Only require current_password if user already has a password
        if ($this->user()->password) {
            $rules['current_password'] = $this->currentPasswordRules();
        }

        return $rules;
    }
}
