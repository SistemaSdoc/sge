<?php

namespace App\Http\Requests\InstituicaoCurso;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorTurnosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'turnos' => ['required', 'array', 'min:1'],
            'turnos.*' => ['exists:turno_Disciplina_Professor,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'turnos.required' => 'O campo turnos é obrigatório.',
            'turnos.array' => 'O campo turnos deve ser uma lista.',
            'turnos.min' => 'Pelo menos um turno deve ser selecionado.',
            'turnos.*.exists' => 'Um dos turnos selecionados é inválido.',
        ];
    }
}
