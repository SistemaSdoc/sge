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
}
