<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o comentário opcional de uma aprovação de tema PAP.
 */
class AprovarTemaPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras do comentário da decisão.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
