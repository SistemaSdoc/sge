<?php

namespace App\Http\Requests\Tenant\Pap\Grupo;

use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a criação de um grupo PAP numa turma.
 */
class CriarGrupoPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de validação do novo grupo.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $cursoTutelado = $this->route('cursoTutelado');
        $turma = $this->route('turma');
        $cursoClasseTurno = $this->route('cursoClasseTurno');

        return [
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => [
                'nullable',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                ),
            ],
            'problema' => ['nullable', 'string'],
            'objectivo_geral' => ['nullable', 'string'],
            'objectivos_especificos' => ['nullable', 'string'],
            'alunos' => ['required', 'array', 'min:1'],
            'alunos.*' => ['exists:alunos,id'],
            'estudo_caso' => [
                'nullable',
                'string',
                new EstudoCasoPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    $this->input('tema_grupo'),
                ),
            ],
            'nota_final' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'data_defesa' => ['nullable', 'date'],
        ];
    }

    /**
     * Devolve as mensagens específicas da criação do grupo.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'alunos.required' => 'Selecciona pelo menos um aluno.',
            'alunos.min' => 'Selecciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos seleccionados não existe.',
        ];
    }
}
