<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteStudentProfileRequest extends FormRequest
{
    /**
     * Autoriza a conclusão dos dados pessoais por alunos autenticados.
     */
    public function authorize(): bool
    {
        return $this->user('tenant')?->hasRole('Aluno') ?? false;
    }

    /**
     * Define as regras de validação dos dados obrigatórios do aluno.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'telefone' => ['required', 'string', 'max:30'],
            'morada' => ['required', 'string', 'max:255'],
            'genero' => ['required', Rule::in(['M', 'F'])],
            'nacionalidade' => ['required', 'string', 'max:255'],
            'naturalidade' => ['required', 'string', 'max:255'],
            'nome_pai' => ['required', 'string', 'max:125'],
            'nome_mae' => ['required', 'string', 'max:125'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'municipio' => ['required', 'string', 'max:255'],
        ];
    }

    /**
        * Define os nomes legíveis dos campos usados nas mensagens de validação.
        *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'telefone' => 'telefone',
            'morada' => 'morada',
            'genero' => 'género',
            'nacionalidade' => 'nacionalidade',
            'naturalidade' => 'naturalidade',
            'nome_pai' => 'nome do pai',
            'nome_mae' => 'nome da mãe',
            'data_nascimento' => 'data de nascimento',
            'municipio' => 'município',
        ];
    }

    /**
        * Define mensagens personalizadas para as regras de validação do perfil.
        *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'genero.in' => 'Selecione um género válido.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}
