<?php

namespace App\Http\Requests\Central\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => $this->passwordRules(),
        ];

        if ($this->user()->password) {
            $rules['current_password'] = $this->currentPasswordRules();
        }

        return $rules;
    }
}
