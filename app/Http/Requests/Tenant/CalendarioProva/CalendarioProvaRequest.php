<?php

namespace App\Http\Requests\Tenant\CalendarioProva;

use App\Models\Central\AnoLectivo;
use App\Models\Central\Disciplina;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class CalendarioProvaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ano_lectivo_id' => [
                'required',
                'uuid',
                Rule::exists(AnoLectivo::class, 'id'),
            ],
            'curso_classe_turno_id' => ['required', 'uuid', 'exists:curso_classe_turno,id'],
            'tipo'                  => ['required', 'string', 'in:trimestral,recurso,exame_especial'],
            'trimestre'             => ['nullable', 'integer', 'in:1,2,3', 'required_if:tipo,trimestral'],
            'entradas'              => ['required', 'array', 'min:1'],
            'entradas.*.disciplina_id' => [
                'required',
                'uuid',
                Rule::exists(Disciplina::class, 'id'),
            ],
            'entradas.*.data'        => ['required', 'date'],
            'entradas.*.hora_inicio' => ['required', 'date_format:H:i'],
            'entradas.*.hora_fim'    => ['required', 'date_format:H:i', 'after:entradas.*.hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'ano_lectivo_id.required'              => 'Selecione o ano lectivo.',
            'ano_lectivo_id.exists'                => 'O ano lectivo seleccionado não existe.',
            'curso_classe_turno_id.required'       => 'Selecione o curso, classe e turno.',
            'curso_classe_turno_id.exists'         => 'O curso/classe/turno seleccionado não existe.',
            'tipo.required'                        => 'Selecione o tipo de calendário.',
            'tipo.in'                              => 'O tipo deve ser Trimestral, Recurso ou Exame Especial.',
            'trimestre.required_if'                => 'O trimestre é obrigatório para calendários trimestrais.',
            'trimestre.in'                         => 'O trimestre deve ser 1, 2 ou 3.',
            'entradas.required'                    => 'Adicione pelo menos uma entrada ao calendário.',
            'entradas.*.disciplina_id.required'    => 'Selecione a disciplina.',
            'entradas.*.disciplina_id.exists'      => 'A disciplina seleccionada não existe.',
            'entradas.*.data.required'             => 'A data é obrigatória.',
            'entradas.*.data.date'                 => 'A data é inválida.',
            'entradas.*.hora_inicio.required'      => 'A hora de início é obrigatória.',
            'entradas.*.hora_inicio.date_format'   => 'A hora de início deve estar no formato HH:MM.',
            'entradas.*.hora_fim.required'         => 'A hora de fim é obrigatória.',
            'entradas.*.hora_fim.date_format'      => 'A hora de fim deve estar no formato HH:MM.',
            'entradas.*.hora_fim.after'            => 'A hora de fim deve ser posterior à hora de início.',
        ];
    }
}