<?php

namespace App\Http\Requests\Tenant\ReclamacaoNota;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DecidirReclamacaoRequest extends FormRequest
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
            'decisao' => ['required', 'in:em_analise,aguarda_informacao,aprovada,reprovada'],
            'nota_corrigida' => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
                'required_if:decisao,aprovada',
            ],
            'parecer' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decisao.required' => 'Selecione uma decisão.',
            'decisao.in' => 'Selecione uma decisão válida.',
            'nota_corrigida.required_if' => 'Informe a nota corrigida quando a reclamação for aprovada.',
            'nota_corrigida.numeric' => 'A nota corrigida deve ser um número.',
            'nota_corrigida.min' => 'A nota corrigida não pode ser menor que 0.',
            'nota_corrigida.max' => 'A nota corrigida não pode ser maior que 20.',
            'parecer.required' => 'Informe o parecer da análise.',
            'parecer.min' => 'O parecer deve conter pelo menos 10 caracteres.',
            'parecer.max' => 'O parecer não pode ter mais que 2000 caracteres.',
        ];
    }
}
