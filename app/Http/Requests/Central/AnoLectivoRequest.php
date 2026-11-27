<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnoLectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SuperAdmin') ?? false;
    }

    public function rules(): array
    {
        return [
            'ano_inicio' => [
                'required',
                'integer',
                'min:2000',
                'max:2200',
                Rule::unique('ano_lectivos', 'ano_inicio')
                    ->ignore($this->route('anoLectivo')?->getKey())
                    ->withoutTrashed(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ano_inicio.required' => 'O ano de início é obrigatório.',
            'ano_inicio.unique' => 'Já existe um ano lectivo para este período.',
            'ano_inicio.min' => 'O ano de início não é válido.',
            'ano_inicio.max' => 'O ano de início não é válido.',
        ];
    }
}
