<?php

namespace App\Http\Requests\Tenant\GrupoPap;

use App\Rules\AlunoNaoPertencenteAoGrupo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'nome_grupo' => 'sometimes|string|max:255',
            'tema_grupo' => 'sometimes|string|max:255',
            'estudo_caso' => 'nullable|string',
            'status' => 'sometimes|string',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'data_defesa' => 'nullable|date',
            'professor_tutor_id' => 'sometimes|exists:professores,id',
            'alunos' => 'sometimes|array|min:1',
            'alunos.*' => [
                'exists:alunos,id',
                new AlunoNaoPertencenteAoGrupo($this->route('grupoPap'), isUpdate: true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_grupo.string' => 'O nome do grupo deve ser texto.',
            'nome_grupo.max' => 'O nome do grupo não pode ter mais de 255 caracteres.',
            'tema_grupo.string' => 'O tema do grupo deve ser texto.',
            'tema_grupo.max' => 'O tema do grupo não pode ter mais de 255 caracteres.',
            'nota_final.numeric' => 'A nota final deve ser um número.',
            'nota_final.min' => 'A nota final deve ser no mínimo 0.',
            'nota_final.max' => 'A nota final deve ser no máximo 20.',
            'data_defesa.date' => 'A data de defesa deve ser uma data válida.',
            'professor_tutor_id.exists' => 'O professor selecionado não existe.',
            'alunos.array' => 'Os alunos devem ser uma lista.',
            'alunos.min' => 'Seleciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos selecionados não existe.',
        ];
    }
}
