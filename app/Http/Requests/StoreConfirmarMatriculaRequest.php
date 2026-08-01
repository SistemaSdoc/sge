<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreConfirmarMatriculaRequest extends FormRequest
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
            'aluno_id' => [
                'required',
                'uuid',
                'exists:alunos,id',
            ],
            'turma_nova_id' => [
                'required',
                'uuid',
                'exists:turmas,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'aluno_id.required' => 'O aluno é obrigatório',
            'aluno_id.uuid' => 'ID do aluno inválido',
            'aluno_id.exists' => 'O aluno selecionado não existe',
            'turma_nova_id.required' => 'A turma nova é obrigatória',
            'turma_nova_id.uuid' => 'ID da turma inválido',
            'turma_nova_id.exists' => 'A turma selecionada não existe',
        ];
    }
}
