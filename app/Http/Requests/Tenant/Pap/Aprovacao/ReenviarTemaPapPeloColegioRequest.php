<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o reenvio do tema no fluxo de colégios.
 */
class ReenviarTemaPapPeloColegioRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de reenvio do tema do colégio.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->route('grupoPap');
        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma?->cursoClasseTurno;
        $cursoTutelado = $cursoClasseTurno?->cursoClasse?->cursoTutelado;

        return [
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTutelado?->getKey(),
                    (string) $turma?->ano_lectivo_id,
                    (string) $turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                    $this->input('estudo_caso', $grupoPap->estudo_caso),
                ),
            ],
        ];
    }

    /**
     * Devolve as mensagens específicas do reenvio da proposta pelo colégio.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'nome_grupo.string' => 'O nome do grupo deve ser um texto.',
            'nome_grupo.max' => 'O nome do grupo não pode exceder 255 caracteres.',
            'tema_grupo.required' => 'O tema do grupo é obrigatório.',
            'tema_grupo.string' => 'O tema do grupo deve ser um texto.',
            'tema_grupo.max' => 'O tema do grupo não pode exceder 255 caracteres.',
        ];
    }
}
