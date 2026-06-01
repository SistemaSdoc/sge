<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClasseTurnoDisciplinaHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fim' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'horarios.required' => 'Selecione pelo menos um dia com horário',
            'horarios.array' => 'Horários devem ser um array',
            'horarios.min' => 'Selecione pelo menos um dia',
            'horarios.*.dia_semana.required' => 'Dia da semana é obrigatório',
            'horarios.*.dia_semana.between' => 'Dia deve ser entre 1 e 7',
            'horarios.*.hora_inicio.required' => 'Hora de início é obrigatória',
            'horarios.*.hora_inicio.date_format' => 'Hora de início deve estar no formato HH:mm',
            'horarios.*.hora_fim.required' => 'Hora de fim é obrigatória',
            'horarios.*.hora_fim.date_format' => 'Hora de fim deve estar no formato HH:mm',
            'horarios.*.hora_fim.after' => 'Hora de fim deve ser após hora de início',
        ];
    }
}
