<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o reenvio de uma proposta PAP após pedido de melhoria.
 */
class ReenviarTemaPapPelaCoordenacaoRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de reenvio usadas no tenant do colégio.
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
            'nome_grupo' => ['required', 'string', 'max:500'],
            'tema_grupo' => [
                'required',
                'string',
                'max:500',
                new TemaPapUnico(
                    (string) $cursoTutelado?->getKey(),
                    (string) $turma?->ano_lectivo_id,
                    (string) $turma?->curso_classe_turno_id,
                    (string) $grupoPap->getKey(),
                ),
            ],
            'problema' => ['nullable', 'string', 'max:2000'],
            'objectivo_geral' => ['nullable', 'string', 'max:2000'],
            'objectivos_especificos' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Devolve as mensagens específicas do reenvio da proposta.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'nome_grupo.string' => 'O nome do grupo deve ser um texto.',
            'nome_grupo.max' => 'O nome do grupo não pode exceder 500 caracteres.',
            'tema_grupo.required' => 'O tema do grupo é obrigatório.',
            'tema_grupo.string' => 'O tema do grupo deve ser um texto.',
            'tema_grupo.max' => 'O tema do grupo não pode exceder 500 caracteres.',
            'problema.string' => 'O problema deve ser um texto.',
            'problema.max' => 'O problema não pode exceder 2000 caracteres.',
            'objectivo_geral.string' => 'O objectivo geral deve ser um texto.',
            'objectivo_geral.max' => 'O objectivo geral não pode exceder 2000 caracteres.',
            'objectivos_especificos.string' => 'Os objectivos específicos devem ser um texto.',
            'objectivos_especificos.max' => 'Os objectivos específicos não podem exceder 2000 caracteres.',
        ];
    }
}
