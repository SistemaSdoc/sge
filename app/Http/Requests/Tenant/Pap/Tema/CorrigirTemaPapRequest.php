<?php

namespace App\Http\Requests\Tenant\Pap\Tema;

use App\Models\Tenant\GrupoPap;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a correcção do tema após um pedido de melhoria do tutor.
 */
class CorrigirTemaPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de correcção do tema do grupo.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $cursoTutelado = $this->route('cursoTutelado');
        $turma = $this->route('turma');
        $cursoClasseTurno = $this->route('cursoClasseTurno');

        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->route('grupoPap');

        return [
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    (string) $grupoPap->getKey(),
                ),
            ],
            'problema' => ['nullable', 'string'],
            'objectivo_geral' => ['nullable', 'string'],
            'objectivos_especificos' => ['nullable', 'string'],
            'estudo_caso' => [
                'nullable',
                'string',
                new EstudoCasoPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    $this->input('tema_grupo', $grupoPap->tema_grupo),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ];
    }

    /**
     * Devolve as mensagens específicas da correcção do tema.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tema_grupo.required' => 'O tema do grupo é obrigatório.',
            'tema_grupo.string' => 'O tema do grupo deve ser texto.',
            'tema_grupo.max' => 'O tema do grupo não pode ter mais de 255 caracteres.',
            'problema.string' => 'O problema deve ser texto.',
            'objectivo_geral.string' => 'O objectivo geral deve ser texto.',
            'objectivos_especificos.string' => 'Os objectivos específicos devem ser texto.',
            'estudo_caso.string' => 'O estudo de caso deve ser texto.',
        ];
    }
}
