<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class TutelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curso_id' => 'exists:cursos,id',
            'nome' => 'required_without:curso_id|string|max:255',
            'duracao' => 'required_without:curso_id|integer|min:1',
            'instituicao_id' => 'required|exists:instituicoes,id',
            'classes' => 'required|array',
            'instituicao_tutora_id' => 'required|exists:instituicoes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'curso_id.exists' => 'O curso selecionado é inválido.',
            'nome.required_without' => 'O nome do curso é obrigatório quando o curso_id não é fornecido.',
            'duracao.required_without' => 'A duração do curso é obrigatória quando o curso_id não é fornecido.',
            'instituicao_id.required' => 'A instituição é obrigatória.',
            'instituicao_id.exists' => 'A instituição selecionada é inválida.',
            'instituicao_tutora_id.required' => 'A instituição é obrigatória.',
            'instituicao_tutora_id.exists' => 'A instituição selecionada é inválida.',
        ];
    }
}
