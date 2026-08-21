<?php

namespace App\Http\Requests\Tenant\ElementosGrupoPap;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarNotaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nota_individual' => 'required|numeric|min:0|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'nota_individual.required' => 'A nota individual é obrigatória.',
            'nota_individual.numeric' => 'A nota individual deve ser um número.',
            'nota_individual.min' => 'A nota individual deve ser no mínimo 0.',
            'nota_individual.max' => 'A nota individual deve ser no máximo 20.',
        ];
    }
}
