<?php

namespace App\Http\Requests\Tenant\Pap\Trabalho;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o ficheiro PDF submetido como trabalho PAP.
 */
class SubmeterTrabalhoPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de submissão do trabalho.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ficheiro' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * Devolve a mensagem apresentada quando o upload falha no servidor.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ficheiro.uploaded' => 'O servidor não conseguiu receber o trabalho. Tente novamente.',
        ];
    }
}
