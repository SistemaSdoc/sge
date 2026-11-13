<?php

namespace App\Http\Requests\Tenant\Pap\Trabalho;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o pedido de correcção do trabalho pelo tutor ou pela coordenação.
 */
class SolicitarCorrecaoTrabalhoPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras do comentário e do ficheiro de correcção.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
