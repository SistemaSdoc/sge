<?php

namespace App\Http\Requests\Classe;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClasseRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u'],
            'ordem' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.regex' => 'Não é permitido inserir números neste campo.',
            'ordem.integer' => 'Ordem deve ser um número inteiro.',
        ];
    }
}
