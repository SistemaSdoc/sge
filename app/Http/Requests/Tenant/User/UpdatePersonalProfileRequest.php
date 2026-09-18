<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalProfileRequest extends FormRequest
{
    /** Autoriza a actualização do próprio perfil. */
    public function authorize(): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = auth('tenant')->user();
        $user = $this->route('user');

        return $user instanceof User && $authenticatedUser?->is($user);
    }

    /** Define as regras dos dados pessoais. */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'bi' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', Rule::in(['M', 'F'])],
            'data_nascimento' => ['nullable', 'date'],
            'nacionalidade' => ['nullable', 'string', 'max:255'],
            'naturalidade' => ['nullable', 'string', 'max:255'],
            'nome_pai' => ['nullable', 'string', 'max:255'],
            'nome_mae' => ['nullable', 'string', 'max:255'],
            'morada' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:15'],
        ];
    }
}
