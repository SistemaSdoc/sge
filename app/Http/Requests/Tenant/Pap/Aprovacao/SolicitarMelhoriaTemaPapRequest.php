<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a recomendação de melhoria de uma proposta PAP.
 */
class SolicitarMelhoriaTemaPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras da recomendação de melhoria.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recomendacao' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Devolve as mensagens específicas do pedido de melhoria.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recomendacao.required' => 'Indique as recomendações de melhoria.',
            'recomendacao.string' => 'As recomendações de melhoria devem ser um texto.',
            'recomendacao.min' => 'As recomendações devem ter pelo menos 10 caracteres.',
            'recomendacao.max' => 'As recomendações não podem exceder 2000 caracteres.',
        ];
    }
}
