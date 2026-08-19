<?php

namespace App\Http\Requests\Central\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterStoreRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'tenant_name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('tenants', 'id'),
                Rule::unique('domains', 'domain'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'titulo.string' => 'O nome deve ser uma string.',
            'titulo.max' => 'O nome não pode exceder 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.string' => 'O email deve ser uma string.',
            'email.email' => 'O email deve ser valido.',
            'tenant_name.required' => 'O nome do tenant é obrigatorio.',
            'tenant_name.string' => 'O nome do tenant deve ser uma string',
            'domain.required' => 'O nome do tenant é obrigatorio.',
            'domain.alpha_dash:ascii' => 'Insira um domnio valido.',
        ];
    }
}
