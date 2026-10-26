<?php

namespace App\Http\Requests\Tenant\InstituicaoCurso;

use App\Rules\CentralAnoLectivoExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professor_id' => ['required', 'exists:professores,id'],
            'ano_lectivo_id' => ['nullable', 'uuid', new CentralAnoLectivoExists],
        ];
    }

    public function messages(): array
    {
        return [
            'professor_id.required' => 'O professor é obrigatório.',
            'professor_id.exists' => 'O professor selecionado é inválido.',
            'ano_lectivo_id.exists' => 'O ano lectivo selecionado é inválido.',
        ];
    }
}
