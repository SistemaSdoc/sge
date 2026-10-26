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
        $studentFieldRequirement = $this->user('tenant')?->hasRole('Aluno')
            ? 'required'
            : 'nullable';

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'bi' => [$studentFieldRequirement, 'string', 'max:255'],
            'genero' => [$studentFieldRequirement, Rule::in(['M', 'F'])],
            'data_nascimento' => [$studentFieldRequirement, 'date'],
            'nacionalidade' => [$studentFieldRequirement, 'string', 'max:255'],
            'naturalidade' => [$studentFieldRequirement, 'string', 'max:255'],
            'nome_pai' => [$studentFieldRequirement, 'string', 'max:255'],
            'nome_mae' => [$studentFieldRequirement, 'string', 'max:255'],
            'morada' => [$studentFieldRequirement, 'string', 'max:255'],
            'municipio' => [$studentFieldRequirement, 'string', 'max:255'],
            'telefone' => [$studentFieldRequirement, 'string', 'max:15'],
        ];
    }
}
