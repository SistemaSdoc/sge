<?php

namespace App\Http\Requests\Tenant\Inscricao;

use App\Rules\CentralAnoLectivoExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ─── Obrigatórios na inscrição ───
            'nome' => 'required|string|max:255',
            'bi' => [
                'required',
                'string',
                'max:20',
                'unique:candidatos,bi',
                'unique:users,bi',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:candidatos,email',
                'unique:users,email',
            ],

            'curso_classe_turno_id' => [
                'required',
                'exists:curso_classe_turno,id',
            ],

            'turma_id' => [
                'nullable',
                'exists:turmas,id',
            ],

            'nota_teste' => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
            ],

            'ano_lectivo_id' => [
                'nullable',
                'uuid',
                new CentralAnoLectivoExists,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'bi.required' => 'O número de BI é obrigatório.',
            'bi.unique' => 'Já existe um registo com este número de BI.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email introduzido não é válido.',
            'email.unique' => 'Já existe um registo com este email.',
            'curso_classe_turno_id.required' => 'O curso/turno é obrigatório.',
            'curso_classe_turno_id.exists' => 'O curso/turno seleccionado não existe.',
            'turma_id.exists' => 'A turma seleccionada não existe.',
            'nota_teste.numeric' => 'A nota deve ser numérica.',
            'nota_teste.min' => 'A nota não pode ser inferior a 0.',
            'nota_teste.max' => 'A nota não pode ser superior a 20.',
        ];
    }
}
