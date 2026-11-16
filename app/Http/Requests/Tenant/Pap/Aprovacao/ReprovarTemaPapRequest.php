<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o motivo obrigatório de reprovação de um tema PAP.
 */
class ReprovarTemaPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras do motivo de reprovação.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Devolve as mensagens específicas da reprovação do tema.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Indique o motivo da reprovação.',
            'motivo.string' => 'O motivo da reprovação deve ser um texto.',
            'motivo.min' => 'O motivo da reprovação deve ter pelo menos 10 caracteres.',
            'motivo.max' => 'O motivo da reprovação não pode exceder 2000 caracteres.',
        ];
    }
}
