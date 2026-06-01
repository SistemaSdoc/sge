<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user')?->id; // pega o ID do user da rota

        return [
            'nome' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => $this->isMethod('post') ? 'required|string|min:6' : 'nullable|string|min:6',
            'instituicao_id' => 'required|exists:instituicoes,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço de email válido.',
            'email.unique' => 'O email já está em uso.',
            'password.required' => 'A senha é obrigatória para criação.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'instituicao_id.required' => 'A instituição é obrigatória.',
            'instituicao_id.exists' => 'A instituição selecionada é inválida.',
            'roles.array' => 'As roles devem ser um array.',
            'roles.*.exists' => 'Uma das roles selecionadas é inválida.',
        ];
    }
}
