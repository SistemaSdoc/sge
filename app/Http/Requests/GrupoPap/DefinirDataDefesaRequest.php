<?php

namespace App\Http\Requests\GrupoPap;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DefinirDataDefesaRequest extends FormRequest
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
        return [
            'data_defesa' => ['required', 'date'],
            'local_defesa' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_defesa.required' => 'A data da defesa é obrigatória.',
            'data_defesa.date' => 'A data da defesa deve ser uma data válida.',
            'local_defesa.required' => 'O local da defesa é obrigatório.',
            'local_defesa.string' => 'O local da defesa deve ser um texto.',
            'local_defesa.max' => 'O local da defesa não pode ter mais de 255 caracteres.',
        ];
    }
}
