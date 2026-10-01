<?php

namespace App\Http\Requests\Central\CalendarioAnual;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarioAnualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'ano' => [
                Rule::requiredIf($isCreate),
                'nullable',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('calendarios_anuais', 'ano')->ignore($this->route('calendarioAnual')),
            ],
            'ficheiro' => [
                $isCreate ? 'required' : 'nullable',
                'file',
                'mimes:pdf,docx',
                'max:102400',
            ],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

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
