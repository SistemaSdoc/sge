<?php

namespace App\Http\Requests\Central\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_name' => $this->input('tenant_name', $this->input('nome')),
            'nome' => $this->input('nome', $this->input('user_nome')),
            'email' => $this->input('email', $this->input('user_email')),
            'sigla' => strtoupper($this->input('sigla', '')),
            'domain' => strtolower($this->input('domain', '')),
        ]);
    }

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
            'sigla' => ['required', 'string', 'max:10'],
            'tipo' => ['required', 'string', 'in:colegio,instituto'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('tenants', 'id'),
                Rule::unique('domains', 'domain'),
            ],
            'user_nome' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'lowercase'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da instituição é obrigatório.',
            'sigla.required' => 'A sigla é obrigatória.',
            'tipo.required' => 'O tipo de instituição é obrigatório.',
            'tipo.in' => 'O tipo deve ser Colégio ou Instituto.',
            'domain.required' => 'O subdomínio é obrigatório.',
            'domain.alpha_dash' => 'O subdomínio só pode conter letras, números e hífens.',
            'domain.unique' => 'Este subdomínio já está em uso.',
            'user_nome.required' => 'O nome do utilizador é obrigatório.',
            'user_email.required' => 'O email do utilizador é obrigatório.',
            'user_email.email' => 'O email deve ser válido.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'As senhas não coincidem.',
        ];
    }
}
