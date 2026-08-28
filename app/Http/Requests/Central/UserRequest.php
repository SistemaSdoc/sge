<?php

namespace App\Http\Requests\Central;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => $this->isMethod('post')
                ? ['required', 'string', 'min:6']
                : ['nullable', 'string', 'min:6'],
            // 'instituicao_id' => ['nullable', 'uuid'],
            // 'bi' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'telefone' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
