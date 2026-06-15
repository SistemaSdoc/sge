<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvisoRequest extends FormRequest
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
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:aviso,evento,urgente',
            'data' => 'nullable|date',
            'ativo' => 'boolean',
            'destinatario' => 'required|in:todos,alunos,professores',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título é obrigatório.',
            'titulo.string' => 'O título deve ser uma string.',
            'titulo.max' => 'O título não pode exceder 255 caracteres.',
            'descricao.string' => 'A descrição deve ser uma string.',
            'tipo.required' => 'O tipo é obrigatório.',
            'tipo.in' => 'O tipo deve ser aviso, evento ou urgente.',
            'data.date' => 'A data deve ser um formato de data válido.',
            'ativo.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
            'destinatario.required' => 'O destinatário é obrigatório.',
            'destinatario.in' => 'O destinatário deve ser todos, alunos ou professores.',
        ];
    }
}
