<?php

namespace App\Http\Requests\Tenant\BancaJuriPap;

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
        $professorRules = ['required', 'uuid'];

        if (! $this->route('colegio')) {
            $professorRules[] = 'exists:professores,id';
            $professorRules[] = new ProfessorNaoNaBanca($this->route('grupoPap'));
        }

        return [
            'professor_id' => $professorRules,
            'professor_externo_tenant_id' => ['nullable', 'string'],
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
