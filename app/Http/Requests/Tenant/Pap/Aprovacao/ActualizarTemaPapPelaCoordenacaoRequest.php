<?php

namespace App\Http\Requests\Tenant\Pap\Aprovacao;

use App\Models\Tenant\GrupoPap;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a actualização do tema após uma melhoria pedida pela coordenação.
 */
class ActualizarTemaPapPelaCoordenacaoRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de actualização do tema após melhoria.
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
            'estudo_caso' => [
                'nullable',
                'string',
                'max:2000',
                new EstudoCasoPapUnico(
                    (string) $cursoTutelado?->getKey(),
                    (string) $turma?->ano_lectivo_id,
                    (string) $turma?->curso_classe_turno_id,
                    $this->input('tema_grupo', $grupoPap->tema_grupo),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ];
    }
}
