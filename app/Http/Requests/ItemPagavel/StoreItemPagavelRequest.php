<?php

namespace App\Http\Requests\ItemPagavel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemPagavelRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in(['financeiro', 'documento'])],
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'frequencia' => ['required', Rule::in(['mensal', 'anual', 'unico'])],
            'curso_classe_id' => ['nullable', 'uuid', 'exists:curso_classe,id'],
            'ativo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do item é obrigatório.',
            'tipo.required' => 'Selecciona o tipo do item.',
            'tipo.in' => 'Tipo inválido. Use financeiro ou documento.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.min' => 'O valor não pode ser negativo.',
            'frequencia.required' => 'Selecione a frequência de pagamento.',
            'frequencia.in' => 'Frequência inválida. Use mensal, anual ou único.',
            'curso_classe_id.exists' => 'Curso/classe inválido.',
        ];
    }
}
