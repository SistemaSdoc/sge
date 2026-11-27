<?php

namespace App\Http\Requests\Central\CalendarioAnual;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarioAnualRequest extends FormRequest
{
    /**
     * Determina se o usuario pode criar um calendario anual.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação para a criação de um calendario anual.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ano' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('calendarios_anuais', 'ano'),
            ],
            'ficheiro' => ['required', 'file', 'mimes:pdf,docx', 'max:102400'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Obtém as mensagens de validação personalizadas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ano.required' => 'O ano lectivo é obrigatório.',
            'ano.regex' => 'O ano lectivo deve estar no formato 2026/2027.',
            'ano.unique' => 'Já existe um calendário para este ano lectivo.',
            'ficheiro.required' => 'Seleccione o ficheiro do calendário.',
            'ficheiro.file' => 'O ficheiro seleccionado é inválido.',
            'ficheiro.mimes' => 'O calendário deve estar no formato PDF ou DOCX.',
            'ficheiro.max' => 'O ficheiro não pode ultrapassar 100 MB.',
            'ativo.boolean' => 'O estado do calendário é inválido.',
        ];
    }
}
