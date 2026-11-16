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

    /**
     * Devolve as mensagens específicas do pedido de correcção do trabalho.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comentario.required' => 'Descreva as correcções necessárias.',
            'comentario.string' => 'O comentário deve ser um texto.',
            'comentario.min' => 'O comentário deve ter pelo menos 10 caracteres.',
            'comentario.max' => 'O comentário não pode exceder 2000 caracteres.',
            'ficheiro.file' => 'O ficheiro de correcção não é válido.',
            'ficheiro.mimes' => 'O ficheiro de correcção deve estar em formato PDF.',
            'ficheiro.max' => 'O ficheiro de correcção não pode exceder 20 MB.',
        ];
    }
}
