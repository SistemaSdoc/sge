<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u'],
            'cursos' => 'required|array',
            'cursos.*' => 'exists:cursos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.regex' => 'Não é permitido inserir números neste campo.',
            'cursos.required' => 'O campo cursos é obrigatório.',
            'cursos.array' => 'O campo cursos deve ser uma lista.',
            'cursos.*.exists' => 'Um dos cursos selecionados é inválido.',
        ];
    }
}
