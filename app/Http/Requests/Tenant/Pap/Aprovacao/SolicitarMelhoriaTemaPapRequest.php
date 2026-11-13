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
}
