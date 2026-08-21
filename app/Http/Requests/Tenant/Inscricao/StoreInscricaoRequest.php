<?php

namespace App\Http\Requests\Tenant\Inscricao;

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
            'nome' => 'required|string|max:255',
            'bi' => [
                'required',
                'string',
                'max:20',
                'unique:candidatos,bi',
                'unique:users,bi',
            ],
            'numero_estudante' => [
                'nullable',
                'string',
                'max:20',
                'unique:candidatos,numero_estudante',
            ],
            'telefone' => [
                'nullable',
                'string',
                'max:20',
                'unique:candidatos,telefone',
                'unique:users,telefone',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:candidatos,email',
                'unique:users,email',
            ],
            'genero' => [
                'required',
                'in:M,F',
            ],
            'nacionalidade' => [
                'required',
                'string',
                'max:255',
            ],
            'naturalidade' => [
                'required',
                'string',
                'max:255',
            ],
            'morada' => [
                'nullable',
                'string',
                'max:255',
            ],
            'filiacao' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data_nascimento' => [
                'required',
                'date',
                'before:today',
            ],

            'municipio' => [
                'required',
                'string',
                'max:255',
            ],

            'curso_classe_turno_id' => [
                'required',
                'exists:curso_classe_turno,id',
            ],
            'turma_id' => [
                'required',
                'exists:turmas,id',
            ],
            'nota_teste' => [
                'required',
                'numeric',
                'min:0',
                'max:20',
            ],
            'ano_lectivo_id' => [
                'nullable',
                'exists:ano_lectivos,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',

            'bi.required' => 'O número de BI é obrigatório.',
            'bi.max' => 'O BI não pode ter mais de 20 caracteres.',
            'bi.unique' => 'Já existe um registo com este número de BI.',

            'numero_estudante.required' => 'O número de estudante é obrigatório.',
            'numero_estudante.max' => 'O número de estudante não pode ter mais de 20 caracteres.',
            'numero_estudante.unique' => 'Já existe um registo com este número de estudante.',

            'telefone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'telefone.unique' => 'Já existe um registo com este número de telefone.',

            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email introduzido não é válido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'email.unique' => 'Já existe um registo com este email.',

            'genero.required' => 'O género é obrigatório.',
            'genero.in' => 'O género deve ser Masculino ou Feminino.',

            'nacionalidade.required' => 'A nacionalidade é obrigatória.',
            'nacionalidade.max' => 'A nacionalidade não pode ter mais de 255 caracteres.',

            'naturalidade.required' => 'A naturalidade é obrigatória.',
            'naturalidade.max' => 'A naturalidade não pode ter mais de 255 caracteres.',

            'filiacao.max' => 'A filiação não pode ter mais de 255 caracteres.',

            'data_nascimento.required' => 'A data de nascimento é obrigatória.',
            'data_nascimento.date' => 'A data de nascimento deve ser uma data válida.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior à data de hoje.',

            'municipio.required' => 'O município é obrigatório.',
            'municipio.max' => 'O município não pode ter mais de 255 caracteres.',

            'curso_classe_turno_id.required' => 'O curso/turno é obrigatório.',
            'curso_classe_turno_id.exists' => 'O curso/turno seleccionado não existe.',

            'turma_id.required' => 'A turma é obrigatória.',
            'turma_id.exists' => 'A turma seleccionada não existe.',

            'nota_teste.required' => 'A nota do teste é obrigatório.',
            'nota_teste.numeric' => 'A nota do teste deve ser um valor numérico.',

            'nota_teste.min' => 'A nota do teste não pode ser inferior a 0.',
            'nota_teste.max' => 'A nota do teste não pode ser superior a 20.',
            'ano_lectivo_id.exists' => 'O ano lectivo seleccionado não existe.',
        ];
    }
}
