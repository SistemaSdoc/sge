<?php

namespace App\Http\Requests\BancaJuriPap;

use App\Rules\ProfessorNaoNaBanca;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'professor_id' => [
                'required',
                'exists:professores,id',
                new ProfessorNaoNaBanca($this->route('grupoPap')),
            ],
            'funcao' => 'required|string|in:Presidente,Vogal 1,Vogal 2',
        ];
    }

    public function messages(): array
    {
        return [
            'professor_id.required' => 'Selecione um professor.',
            'professor_id.exists' => 'O professor selecionado não existe.',
            'funcao.required' => 'Selecione uma função.',
            'funcao.in' => 'A função deve ser Presidente, Vogal 1 ou Vogal 2.',
        ];
    }
}
