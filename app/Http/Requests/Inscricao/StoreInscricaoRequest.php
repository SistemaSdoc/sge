<?php

namespace App\Http\Requests\Inscricao;

use App\Models\AnoLectivo;
use App\Models\CursoClasseTurno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInscricaoRequest extends FormRequest
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
            'nome' => 'required|string|max:255',
            'bi' => [
                'required',
                'string',
                'max:20',
                'unique:candidatos,bi',
                'unique:users,bi',
            ],
            'numero_estudante' => [
                'required',
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
            'curso_classe_turno_id' => [
                'required',
                'exists:curso_classe_turno,id',
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

            'curso_classe_turno_id.required' => 'O curso/turno é obrigatório.',
            'curso_classe_turno_id.exists' => 'O curso/turno seleccionado não existe.',
            'ano_lectivo_id.exists' => 'O ano lectivo seleccionado não existe.',
        ];
    }
}
