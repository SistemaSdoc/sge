<?php

namespace App\Http\Requests\Tenant\RegraAvaliacao;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegraAvaliacaoRequest extends FormRequest
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
            'nivel_ensino_id' => 'nullable|exists:niveis_ensino,id',
            'classe_id' => 'nullable|exists:classes,id',
            'nome' => 'required|string|max:255',
            'media_minima_aprovacao' => 'required|numeric|min:0|max:20',
            'frequencia_minima' => 'required|numeric|min:0|max:100',
            'permite_recurso' => 'boolean',
            'nota_minima_recurso' => 'required_if:permite_recurso,true|nullable|numeric|min:0|max:20',
            'max_disciplinas_negativas' => 'nullable|integer|min:0|max:100',
            'activo' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'classe_id.exists' => 'A classe selecionada não existe.',
            'nivel_ensino_id.exists' => 'O nível de ensino selecionado não existe.',

            'nome.required' => 'O nome da regra é obrigatório.',
            'nome.max' => 'O nome da regra não pode ter mais que 255 caracteres.',

            'media_minima_aprovacao.required' => 'A nota mínima para aprovação é obrigatória.',
            'media_minima_aprovacao.numeric' => 'A nota mínima deve ser um número.',
            'media_minima_aprovacao.min' => 'A nota mínima não pode ser menor que 0.',
            'media_minima_aprovacao.max' => 'A nota mínima não pode ser maior que 20.',

            'frequencia_minima.required' => 'A frequência mínima é obrigatória.',
            'frequencia_minima.numeric' => 'A frequência mínima deve ser um número.',
            'frequencia_minima.min' => 'A frequência mínima não pode ser menor que 0%.',
            'frequencia_minima.max' => 'A frequência mínima não pode ser maior que 100%.',

            'nota_minima_recurso.required_if' => 'A nota mínima de recurso é obrigatória quando o recurso é permitido.',
            'nota_minima_recurso.numeric' => 'A nota mínima de recurso deve ser um número.',
            'nota_minima_recurso.min' => 'A nota mínima de recurso não pode ser menor que 0.',
            'nota_minima_recurso.max' => 'A nota mínima de recurso não pode ser maior que 20.',

            'max_disciplinas_negativas.integer' => 'O limite de disciplinas negativas deve ser um número inteiro.',
            'max_disciplinas_negativas.min' => 'O limite de disciplinas negativas não pode ser negativo.',
            'max_disciplinas_negativas.max' => 'O limite de disciplinas negativas não pode ser maior que 100.',

            'permite_recurso.boolean' => 'O campo "permite recurso" deve ser verdadeiro ou falso.',
            'activo.boolean' => 'O campo "ativo" deve ser verdadeiro ou falso.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'permite_recurso' => $this->boolean('permite_recurso'),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
