<?php

namespace App\Http\Requests\Tenant\GrupoPap;

use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a correcção do tema de um grupo PAP.
 */
class ActualizarTemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $this->route('cursoTutelado')->getKey(),
                    (string) $this->route('turma')->ano_lectivo_id,
                    (string) $this->route('cursoClasseTurno')->getKey(),
                    (string) $this->route('grupoPap')->getKey(),
                ),
            ],
            'problema' => ['nullable', 'string'],
            'objectivos' => ['nullable', 'string'],
            'estudo_caso' => [
                'nullable',
                'string',
                new EstudoCasoPapUnico(
                    (string) $this->route('cursoTutelado')->getKey(),
                    (string) $this->route('turma')->ano_lectivo_id,
                    (string) $this->route('cursoClasseTurno')->getKey(),
                    $this->input('tema_grupo', $this->route('grupoPap')?->tema_grupo),
                    (string) $this->route('grupoPap')->getKey(),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tema_grupo.required' => 'O tema do grupo é obrigatório.',
            'tema_grupo.string' => 'O tema do grupo deve ser texto.',
            'tema_grupo.max' => 'O tema do grupo não pode ter mais de 255 caracteres.',
            'problema.string' => 'O problema deve ser texto.',
            'objectivos.string' => 'Os objectivos devem ser texto.',
            'estudo_caso.string' => 'O estudo de caso deve ser texto.',
        ];
    }
}
