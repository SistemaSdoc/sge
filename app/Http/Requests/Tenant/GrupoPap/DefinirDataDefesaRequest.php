<?php

namespace App\Http\Requests\Tenant\GrupoPap;

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
            'data_defesa' => ['required', 'date_format:Y-m-d'],
            'hora_defesa' => ['required', 'date_format:H:i'],
            'local_defesa' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_defesa.required' => 'A data da defesa é obrigatória.',
            'data_defesa.date_format' => 'A data da defesa deve ser uma data válida.',
            'hora_defesa.required' => 'A hora da defesa é obrigatória.',
            'hora_defesa.date_format' => 'A hora deve estar no formato HH:MM.',
            'local_defesa.required' => 'O local da defesa é obrigatório.',
            'local_defesa.string' => 'O local da defesa deve ser um texto.',
            'local_defesa.max' => 'O local da defesa não pode ter mais de 255 caracteres.',
        ];
    }
}
