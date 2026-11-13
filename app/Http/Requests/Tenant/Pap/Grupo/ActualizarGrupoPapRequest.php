<?php

namespace App\Http\Requests\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Rules\AlunoNaoPertencenteAoGrupo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a alteração dos dados de identificação e composição do grupo.
 */
class ActualizarGrupoPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de alteração dos dados do grupo.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->route('grupoPap');

        return [
            'nome_grupo' => ['sometimes', 'string', 'max:255'],
            'professor_tutor_id' => ['sometimes', 'exists:professores,id'],
            'alunos' => ['sometimes', 'array', 'min:1'],
            'alunos.*' => [
                'exists:alunos,id',
                new AlunoNaoPertencenteAoGrupo($grupoPap, isUpdate: true),
            ],
        ];
    }

    /**
     * Devolve as mensagens específicas da alteração do grupo.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_grupo.string' => 'O nome do grupo deve ser texto.',
            'nome_grupo.max' => 'O nome do grupo não pode ter mais de 255 caracteres.',
            'professor_tutor_id.exists' => 'O professor seleccionado não existe.',
            'alunos.array' => 'Os alunos devem ser uma lista.',
            'alunos.min' => 'Selecciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos seleccionados não existe.',
        ];
    }
}
