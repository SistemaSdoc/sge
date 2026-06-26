<?php

namespace App\Http\Requests\BancaJuriPap;

use App\Rules\ProfessorNaoNaBanca;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'professor_id' => [
                'required',
                'exists:professores,id',
                new ProfessorNaoNaBanca($this->route('grupoPap'), $this->route('bancaJuriPap')),
            ],
            'funcao' => 'required|string|in:Presidente,Vogal 1,Vogal 2',
        ];
    }

    public function messages(): array
    {
        return [
            'professor_id.required' => 'Seleciona um professor.',
            'professor_id.exists' => 'O professor selecionado não existe.',
            'funcao.required' => 'Seleciona uma função.',
            'funcao.in' => 'A função deve ser Presidente, Vogal 1 ou Vogal 2.',
        ];
    }
}