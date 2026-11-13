<?php

namespace App\Http\Requests\Tenant\Pap\Defesa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a data, hora e local da defesa do grupo PAP.
 */
class DefinirDataDefesaRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de agendamento da defesa.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data_defesa' => ['required', 'date_format:Y-m-d'],
            'hora_defesa' => ['required', 'date_format:H:i'],
            'local_defesa' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Devolve as mensagens específicas do agendamento da defesa.
     *
     * @return array<string, string>
     */
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
