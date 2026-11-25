<?php

namespace App\Http\Requests\Central\DeclaracaoEstagio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDeclaracaoEstagioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'responsavel_nome' => $this->normalizarTexto($this->input('responsavel_nome')),
            'responsavel_cargo' => $this->normalizarTexto($this->input('responsavel_cargo')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'aluno_id' => ['required', 'uuid'],
            'nota' => ['required', 'numeric', 'between:0,20'],
            'responsavel_nome' => [
                'required',
                'string',
                'min:3',
                'max:100',
                "regex:/\\A(?=.*\\p{L})[\\p{L}\\p{M} .'-]+\\z/u",
            ],
            'responsavel_cargo' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/\\A(?=.*\\p{L})[^\\p{N}]+\\z/u',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Selecione uma instituição.',
            'tenant_id.exists' => 'A instituição selecionada não é válida.',
            'aluno_id.required' => 'Selecione um aluno.',
            'aluno_id.uuid' => 'O aluno selecionado não é válido.',
            'nota.required' => 'A nota é obrigatória.',
            'nota.numeric' => 'A nota deve ser um valor numérico.',
            'nota.between' => 'A nota deve estar entre 0 e 20 valores.',
            'responsavel_nome.required' => 'O nome do responsável é obrigatório.',
            'responsavel_nome.string' => 'O nome do responsável deve ser texto.',
            'responsavel_nome.min' => 'O nome do responsável deve ter pelo menos 3 caracteres.',
            'responsavel_nome.max' => 'O nome do responsável não pode exceder 100 caracteres.',
            'responsavel_nome.regex' => 'O nome do responsável só pode conter letras, espaços, apóstrofo, ponto e hífen.',
            'responsavel_cargo.required' => 'O cargo do responsável é obrigatório.',
            'responsavel_cargo.string' => 'O cargo do responsável deve ser texto.',
            'responsavel_cargo.min' => 'O cargo do responsável deve ter pelo menos 2 caracteres.',
            'responsavel_cargo.max' => 'O cargo do responsável não pode exceder 80 caracteres.',
            'responsavel_cargo.regex' => 'O cargo deve conter pelo menos uma letra e não pode conter números.',
        ];
    }

    private function normalizarTexto(mixed $value): mixed
    {
        return is_string($value) ? Str::squish($value) : $value;
    }
}
